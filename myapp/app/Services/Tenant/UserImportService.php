<?php

namespace App\Services\Tenant;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserImportService
{
    public function validateCsv(string $csvContent, Tenant $tenant): array
    {
        $lines = array_filter(explode("\n", $csvContent), fn ($line) => trim($line) !== '');

        if (count($lines) < 2) {
            return ['valid' => [], 'errors' => [['row' => 1, 'message' => 'CSV must have a header row and at least one data row.']]];
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $required = ['name', 'email', 'role'];
        $missing = array_diff($required, $header);
        if (! empty($missing)) {
            return ['valid' => [], 'errors' => [['row' => 1, 'message' => 'Missing required columns: ' . implode(', ', $missing)]]];
        }

        $roles = Role::forTenant($tenant->id)->get()->keyBy(fn ($r) => strtolower($r->name));
        $branches = Branch::forTenant($tenant)->get()->keyBy(fn ($b) => strtolower($b->name));
        $existingEmails = User::whereIn('email', collect($lines)->map(fn ($l) => str_getcsv($l)[$this->colIndex($header, 'email')] ?? '')->filter())->pluck('email')->map(fn ($e) => strtolower($e))->toArray();

        $valid = [];
        $errors = [];
        $seenEmails = [];

        foreach ($lines as $i => $line) {
            $row = $i + 2; // 1-indexed, accounting for header
            $cols = str_getcsv($line);
            $data = [];
            foreach ($header as $idx => $col) {
                $data[$col] = trim($cols[$idx] ?? '');
            }

            // Validate name
            if (empty($data['name'])) {
                $errors[] = ['row' => $row, 'message' => 'Name is required.', 'data' => $data];
                continue;
            }

            // Validate email
            if (empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['row' => $row, 'message' => 'Valid email is required.', 'data' => $data];
                continue;
            }

            $emailLower = strtolower($data['email']);
            if (in_array($emailLower, $existingEmails)) {
                $errors[] = ['row' => $row, 'message' => "Email {$data['email']} already exists.", 'data' => $data];
                continue;
            }
            if (in_array($emailLower, $seenEmails)) {
                $errors[] = ['row' => $row, 'message' => "Duplicate email {$data['email']} in CSV.", 'data' => $data];
                continue;
            }
            $seenEmails[] = $emailLower;

            // Validate role
            $roleName = strtolower($data['role'] ?? '');
            if (empty($roleName) || ! $roles->has($roleName)) {
                $errors[] = ['row' => $row, 'message' => "Role '{$data['role']}' not found.", 'data' => $data];
                continue;
            }
            $role = $roles->get($roleName);
            if ($role->slug === 'owner') {
                $errors[] = ['row' => $row, 'message' => 'Cannot assign Owner role via import.', 'data' => $data];
                continue;
            }

            // Validate branch (optional)
            $branchId = null;
            if (! empty($data['branch'])) {
                $branchName = strtolower($data['branch']);
                if (! $branches->has($branchName)) {
                    $errors[] = ['row' => $row, 'message' => "Branch '{$data['branch']}' not found.", 'data' => $data];
                    continue;
                }
                $branchId = $branches->get($branchName)->id;
            }

            $valid[] = [
                'row' => $row,
                'name' => $data['name'],
                'email' => $data['email'],
                'role_id' => $role->id,
                'role_name' => $role->name,
                'branch_id' => $branchId,
                'branch_name' => $data['branch'] ?? null,
            ];
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    public function import(Tenant $tenant, array $validRows): array
    {
        PlanLimitService::ensureWithinLimit($tenant, 'users', count($validRows));

        $credentials = [];

        DB::transaction(function () use ($tenant, $validRows, &$credentials) {
            foreach ($validRows as $row) {
                $password = Str::random(12);

                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $password,
                ]);

                TenantUser::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'role_id' => $row['role_id'],
                    'branch_id' => $row['branch_id'],
                ]);

                $credentials[] = [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => $password,
                    'role' => $row['role_name'],
                ];
            }
        });

        return $credentials;
    }

    private function colIndex(array $header, string $col): int
    {
        return array_search($col, $header) ?: 0;
    }
}
