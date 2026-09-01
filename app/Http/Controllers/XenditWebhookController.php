<?php

namespace App\Http\Controllers;

use App\Models\CreditPurchase;
use App\Models\CreditTransaction;
use App\Services\Credits\CreditLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Receives Xendit invoice webhooks. This is the ONLY place credits from a
 * purchase are added, because it is the only signal we trust that money moved.
 *
 * Two guarantees matter here:
 *   1. Authenticity — the request must carry the callback token from our Xendit
 *      dashboard, or it is ignored. Anyone could otherwise POST themselves free
 *      credits.
 *   2. Idempotency — Xendit may deliver the same event more than once. Crediting
 *      is wrapped in a locked transaction and guarded by credit_transaction_id,
 *      so a purchase can only ever be credited once.
 */
class XenditWebhookController extends Controller
{
    public function __invoke(Request $request, CreditLedger $credits): JsonResponse
    {
        $expected = (string) config('emailcv.xendit.callback_token');
        $provided = (string) $request->header('x-callback-token', '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            Log::warning('Rejected a Xendit webhook with a bad callback token.');

            return response()->json(['message' => 'Invalid callback token.'], 401);
        }

        $externalId = (string) $request->input('external_id', '');
        $status = (string) $request->input('status', '');

        if ($externalId === '') {
            return response()->json(['message' => 'Missing external_id.'], 422);
        }

        $purchase = CreditPurchase::query()->where('external_id', $externalId)->first();

        if ($purchase === null) {
            // Not one of ours (or already pruned). Acknowledge so Xendit stops
            // retrying, but do nothing.
            return response()->json(['message' => 'Unknown purchase.']);
        }

        // Only a paid invoice grants credits. Everything else just records the
        // terminal state for the user's history.
        if ($status !== 'PAID' && $status !== 'SETTLED') {
            if ($status === 'EXPIRED') {
                $purchase->forceFill(['status' => CreditPurchase::STATUS_EXPIRED])->save();
            }

            return response()->json(['message' => 'Acknowledged.']);
        }

        DB::transaction(function () use ($purchase, $credits): void {
            // Re-read under a lock so two concurrent deliveries cannot both pass
            // the already-credited check.
            $locked = CreditPurchase::query()->lockForUpdate()->find($purchase->id);

            if ($locked === null || $locked->credit_transaction_id !== null) {
                // Already credited by an earlier delivery — nothing to do.
                return;
            }

            $transaction = $credits->grant(
                $locked->user,
                CreditTransaction::PURCHASE,
                $locked->credits,
                ['description' => $locked->credits.' credits purchased'],
            );

            $locked->forceFill([
                'status' => CreditPurchase::STATUS_PAID,
                'credit_transaction_id' => $transaction->id,
                'paid_at' => now(),
            ])->save();
        });

        return response()->json(['message' => 'Credited.']);
    }
}
