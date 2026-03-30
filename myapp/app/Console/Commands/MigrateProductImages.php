<?php

namespace App\Console\Commands;

use App\Models\Tenant\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateProductImages extends Command
{
    protected $signature = 'products:migrate-images {--dry-run : Preview changes without executing}';

    protected $description = 'Migrate product images from public disk to the configured product storage disk';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $targetDiskName = config('filesystems.product_disk', 'local');
        $sourceDisk = Storage::disk('public');
        $targetDisk = Storage::disk($targetDiskName);

        $this->info("Migrating product images from 'public' disk to '{$targetDiskName}' disk.");
        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $products = Product::whereNotNull('image_path')->get();
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($products as $product) {
            $oldPath = $product->image_path;
            $filename = basename($oldPath);
            $newPath = "{$product->tenant_id}/products/{$product->id}/{$filename}";

            if ($targetDisk->exists($newPath)) {
                $this->line("  SKIP (already exists): {$oldPath} → {$newPath}");
                $skipped++;
                continue;
            }

            if (!$sourceDisk->exists($oldPath)) {
                $this->error("  FAIL (source missing): {$oldPath}");
                $failed++;
                continue;
            }

            $this->line("  MIGRATE: {$oldPath} → {$newPath}");

            if (!$dryRun) {
                try {
                    $targetDisk->put($newPath, $sourceDisk->get($oldPath));
                    $product->update(['image_path' => $newPath]);
                    $sourceDisk->delete($oldPath);
                } catch (\Throwable $e) {
                    $this->error("  FAIL: {$e->getMessage()}");
                    $failed++;
                    continue;
                }
            }

            $migrated++;
        }

        $this->newLine();
        $this->info("Results: {$migrated} migrated, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
