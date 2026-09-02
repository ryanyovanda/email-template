<?php

namespace App\Http\Controllers;

use App\Models\CreditPackage;
use App\Models\CreditPurchase;
use App\Models\CreditTransaction;
use App\Services\Credits\CreditLedger;
use App\Services\Payments\PaymentException;
use App\Services\Payments\XenditClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Buying credits. The catalogue is admin-defined packages plus an optional
 * free-form amount. A checkout creates a pending purchase and a Xendit invoice,
 * then sends the user to Xendit's hosted page; the credits are only added once
 * the paid webhook arrives.
 */
class CreditPurchaseController extends Controller
{
    public function index(Request $request, CreditLedger $credits): Response
    {
        $pricePerCredit = (int) config('emailcv.xendit.price_per_credit');

        return Inertia::render('credits/Buy', [
            'balance' => $credits->balance($request->user()),
            'packages' => CreditPackage::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get()
                ->map(fn (CreditPackage $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'credits' => $p->credits,
                    'price' => $p->price,
                ]),
            'custom' => [
                'pricePerCredit' => $pricePerCredit,
                'minAmount' => (int) config('emailcv.xendit.min_amount'),
                'maxAmount' => (int) config('emailcv.xendit.max_amount'),
            ],
            'currency' => (string) config('emailcv.xendit.currency', 'IDR'),
            'configured' => app(XenditClient::class)->isConfigured(),
            'recent' => CreditPurchase::query()
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (CreditPurchase $p): array => [
                    'id' => $p->id,
                    'credits' => $p->credits,
                    'amount' => $p->amount,
                    'status' => $p->status,
                    'at' => $p->created_at?->diffForHumans(),
                ]),
        ]);
    }

    public function checkout(Request $request, XenditClient $xendit): SymfonyResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'package_id' => ['nullable', 'integer', 'exists:credit_packages,id'],
            'credits' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        // Resolve how many credits and what it costs, from either a package or
        // the custom amount. Prices are computed server-side; the client never
        // sends a price.
        if (! empty($validated['package_id'])) {
            /** @var CreditPackage $package */
            $package = CreditPackage::query()->active()->findOrFail($validated['package_id']);
            $credits = $package->credits;
            $amount = $package->price;
            $packageId = $package->id;
        } elseif (! empty($validated['credits'])) {
            $pricePerCredit = (int) config('emailcv.xendit.price_per_credit');
            $credits = $validated['credits'];
            $amount = $credits * $pricePerCredit;
            $packageId = null;
        } else {
            return back()->withErrors(['credits' => 'Choose a package or enter how many credits you want.']);
        }

        $min = (int) config('emailcv.xendit.min_amount');
        $max = (int) config('emailcv.xendit.max_amount');

        if ($amount < $min) {
            return back()->withErrors(['credits' => "The minimum top-up is {$min} ".config('emailcv.xendit.currency').'.']);
        }

        if ($amount > $max) {
            return back()->withErrors(['credits' => "The maximum top-up is {$max} ".config('emailcv.xendit.currency').'.']);
        }

        $externalId = 'credit-'.$user->id.'-'.Str::lower(Str::random(16));

        $purchase = CreditPurchase::create([
            'user_id' => $user->id,
            'credit_package_id' => $packageId,
            'credits' => $credits,
            'amount' => $amount,
            'currency' => (string) config('emailcv.xendit.currency', 'IDR'),
            'status' => CreditPurchase::STATUS_PENDING,
            'external_id' => $externalId,
        ]);

        try {
            $invoice = $xendit->createInvoice(
                externalId: $externalId,
                amount: $amount,
                description: $credits.' ApplyMail credits',
                payerEmail: (string) $user->email,
                successUrl: route('credits.index'),
                failureUrl: route('credits.index'),
            );
        } catch (PaymentException $e) {
            $purchase->forceFill(['status' => CreditPurchase::STATUS_FAILED])->save();

            return back()->withErrors(['credits' => $e->getMessage()]);
        }

        $purchase->forceFill([
            'xendit_invoice_id' => $invoice['id'],
            'invoice_url' => $invoice['invoice_url'],
        ])->save();

        // Send the buyer to Xendit's hosted checkout.
        return Inertia::location($invoice['invoice_url']);
    }

    /**
     * The signed-in user's full transaction history: the credit ledger (every
     * grant and spend) plus their credit purchases with invoice links.
     */
    public function history(Request $request, CreditLedger $credits): Response
    {
        $user = $request->user();

        $ledger = CreditTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn (CreditTransaction $t): array => [
                'id' => $t->id,
                'amount' => $t->amount,
                'reason' => $t->reason,
                'label' => $t->label(),
                'description' => $t->description,
                'at' => $t->created_at?->toDayDateTimeString(),
            ]);

        return Inertia::render('credits/History', [
            'balance' => $credits->balance($user),
            'currency' => (string) config('emailcv.xendit.currency', 'IDR'),
            'ledger' => $ledger,
            'purchases' => CreditPurchase::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (CreditPurchase $p): array => [
                    'id' => $p->id,
                    'credits' => $p->credits,
                    'amount' => $p->amount,
                    'currency' => $p->currency,
                    'status' => $p->status,
                    'invoiceUrl' => $p->invoice_url,
                    'at' => $p->created_at?->toDayDateTimeString(),
                    'paidAt' => $p->paid_at?->toDayDateTimeString(),
                ]),
        ]);
    }

    /**
     * A printable invoice for one of the user's own purchases. A user may only
     * ever see their own; anything else is a 404 so ids can't be probed.
     */
    public function invoice(Request $request, CreditPurchase $purchase): Response
    {
        abort_unless($purchase->user_id === $request->user()->id, 404);

        return Inertia::render('credits/Invoice', [
            'invoice' => [
                'id' => $purchase->id,
                'number' => 'APM-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT),
                'credits' => $purchase->credits,
                'amount' => $purchase->amount,
                'currency' => $purchase->currency,
                'status' => $purchase->status,
                'reference' => $purchase->external_id,
                'createdAt' => $purchase->created_at?->toDayDateTimeString(),
                'paidAt' => $purchase->paid_at?->toDayDateTimeString(),
            ],
            'buyer' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }
}
