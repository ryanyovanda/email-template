<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Services\Settings\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Runtime settings the admin controls: what each AI action costs, the monthly
 * allowance, the promotion reward, and the credit packages on sale. Prices live
 * in the settings store (DB), so a change takes effect without a redeploy.
 */
class SettingController extends Controller
{
    public function index(Settings $settings): Response
    {
        return Inertia::render('admin/Settings', [
            'pricing' => [
                'applicationDraft' => $settings->priceOf('application_draft'),
                'templateDesign' => $settings->priceOf('template_design'),
                'monthlyGrant' => $settings->monthlyGrant(),
                'promotionReward' => $settings->promotionReward(),
            ],
            'pricePerCredit' => (int) config('emailcv.xendit.price_per_credit'),
            'currency' => (string) config('emailcv.xendit.currency', 'IDR'),
            'paymentsConfigured' => filled(config('emailcv.xendit.secret_key')),
            'packages' => CreditPackage::query()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get()
                ->map(fn (CreditPackage $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'credits' => $p->credits,
                    'price' => $p->price,
                    'is_active' => $p->is_active,
                    'sort_order' => $p->sort_order,
                ]),
        ]);
    }

    public function updatePricing(Request $request, Settings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'applicationDraft' => ['required', 'integer', 'min:0', 'max:100000'],
            'templateDesign' => ['required', 'integer', 'min:0', 'max:100000'],
            'monthlyGrant' => ['required', 'integer', 'min:0', 'max:1000000'],
            'promotionReward' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $settings->set(Settings::PRICE_APPLICATION_DRAFT, (int) $validated['applicationDraft']);
        $settings->set(Settings::PRICE_TEMPLATE_DESIGN, (int) $validated['templateDesign']);
        $settings->set(Settings::MONTHLY_GRANT, (int) $validated['monthlyGrant']);
        $settings->set(Settings::PROMOTION_REWARD, (int) $validated['promotionReward']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pricing updated.']);

        return back();
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $validated = $this->validatePackage($request);

        CreditPackage::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Package created.']);

        return back();
    }

    public function updatePackage(Request $request, CreditPackage $package): RedirectResponse
    {
        $validated = $this->validatePackage($request);

        $package->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Package updated.']);

        return back();
    }

    public function destroyPackage(CreditPackage $package): RedirectResponse
    {
        $package->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Package deleted.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'credits' => ['required', 'integer', 'min:1', 'max:1000000'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:1000'],
        ]);
    }
}
