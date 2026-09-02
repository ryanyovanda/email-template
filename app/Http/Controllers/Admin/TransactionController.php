<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditPurchase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin view of every credit purchase, with a status filter and headline
 * revenue figures (paid purchases only).
 */
class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = (string) $request->query('status', '');

        $valid = [
            CreditPurchase::STATUS_PENDING,
            CreditPurchase::STATUS_PAID,
            CreditPurchase::STATUS_EXPIRED,
            CreditPurchase::STATUS_FAILED,
        ];

        $transactions = CreditPurchase::query()
            ->with('user:id,name,email')
            ->when(in_array($filter, $valid, true), fn ($q) => $q->where('status', $filter))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CreditPurchase $p): array => [
                'id' => $p->id,
                'number' => 'APM-'.str_pad((string) $p->id, 6, '0', STR_PAD_LEFT),
                'user' => $p->user
                    ? ['name' => $p->user->name, 'email' => $p->user->email]
                    : null,
                'credits' => $p->credits,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'reference' => $p->external_id,
                'at' => $p->created_at?->toDayDateTimeString(),
                'paidAt' => $p->paid_at?->toDayDateTimeString(),
            ]);

        // Headline figures: only paid purchases count as revenue.
        $paid = CreditPurchase::query()->where('status', CreditPurchase::STATUS_PAID);

        return Inertia::render('admin/Transactions', [
            'transactions' => $transactions,
            'filters' => ['status' => $filter],
            'currency' => (string) config('emailcv.xendit.currency', 'IDR'),
            'stats' => [
                'revenue' => (int) (clone $paid)->sum('amount'),
                'paidCount' => (clone $paid)->count(),
                'creditsSold' => (int) (clone $paid)->sum('credits'),
                'pendingCount' => CreditPurchase::query()
                    ->where('status', CreditPurchase::STATUS_PENDING)
                    ->count(),
            ],
        ]);
    }
}
