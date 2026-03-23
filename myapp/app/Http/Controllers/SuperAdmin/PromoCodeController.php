<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Services\Central\AdminActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    public function __construct(
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(): Response
    {
        $promoCodes = PromoCode::withCount('subscriptions')
            ->latest()
            ->paginate(15);

        return Inertia::render('admin/promo-codes/Index', [
            'promoCodes' => $promoCodes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/promo-codes/Create', [
            'plans' => Plan::active()->get(['id', 'name', 'slug']),
            'discountTypes' => collect(DiscountType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $validated['code'] = strtoupper($validated['code']);
        $validated['applicable_plans'] = ! empty($validated['applicable_plans']) ? $validated['applicable_plans'] : null;

        $promoCode = PromoCode::create($validated);

        $this->activityLog->log(
            $request->user('admin'),
            'created_promo_code',
            $promoCode,
            ['code' => $promoCode->code],
            $request->ip(),
        );

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code created successfully.');
    }

    public function edit(int $id): Response
    {
        $promoCode = PromoCode::findOrFail($id);

        return Inertia::render('admin/promo-codes/Edit', [
            'promoCode' => $promoCode,
            'plans' => Plan::active()->get(['id', 'name', 'slug']),
            'discountTypes' => collect(DiscountType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $promoCode = PromoCode::findOrFail($id);

        $validated = $request->validate($this->rules($id));

        $validated['code'] = strtoupper($validated['code']);
        $validated['applicable_plans'] = ! empty($validated['applicable_plans']) ? $validated['applicable_plans'] : null;

        $promoCode->update($validated);

        $this->activityLog->log(
            $request->user('admin'),
            'updated_promo_code',
            $promoCode,
            ['code' => $promoCode->code],
            $request->ip(),
        );

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $promoCode = PromoCode::findOrFail($id);
        $code = $promoCode->code;

        $promoCode->delete();

        $this->activityLog->log(
            $request->user('admin'),
            'deleted_promo_code',
            null,
            ['code' => $code],
            $request->ip(),
        );

        return back()->with('success', 'Promo code deleted successfully.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:promo_codes,code' . ($ignoreId ? ",{$ignoreId}" : '')],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', new Enum(DiscountType::class)],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
            'applicable_plans' => ['nullable', 'array'],
            'applicable_plans.*' => ['string', 'exists:plans,slug'],
        ];
    }
}
