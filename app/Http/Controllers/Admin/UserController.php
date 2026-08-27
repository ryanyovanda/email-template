<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Models\AiGeneration;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Credits\CreditLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, CreditLedger $credits): Response
    {
        $filter = $request->string('filter')->toString();

        $users = User::query()
            ->withCount([
                'applications',
                'aiGenerations as ai_generations_this_month_count' => fn ($query) => $query
                    ->where('status', 'success')
                    ->where('created_at', '>=', now()->startOfMonth()),
                'aiGenerations as ai_generations_today_count' => fn ($query) => $query
                    ->where('status', 'success')
                    ->where('created_at', '>=', now()->startOfDay()),
            ])
            ->with('profile:id,user_id,full_name,cv_filename')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
            })
            ->when($filter === 'banned', fn ($query) => $query->whereNotNull('banned_at'))
            ->when($filter === 'admins', fn ($query) => $query->where('role', 'admin'))
            ->when($filter === 'heavy', fn ($query) => $query->whereIn('id', $this->heavyUserIds()))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'banned_at' => $user->banned_at?->toDateTimeString(),
                'ban_reason' => $user->ban_reason,
                'credits' => $credits->balance($user),
                'applications_count' => $user->applications_count,
                'ai_today' => $user->ai_generations_today_count,
                'ai_month' => $user->ai_generations_this_month_count,
                'full_name' => $user->profile?->full_name,
                'has_cv' => filled($user->profile?->cv_filename),
                'created_at' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'filter' => $filter,
            ],
            'defaults' => [
                'monthlyGrant' => (int) config('emailcv.credits.monthly_grant'),
                'draftPrice' => (int) config('emailcv.credits.prices.application_draft'),
                'templatePrice' => (int) config('emailcv.credits.prices.template_design'),
                'abuseThreshold' => (int) config('emailcv.ai.abuse_threshold_per_day'),
            ],
        ]);
    }

    /**
     * Users past the daily generation threshold, resolved as a plain aggregate
     * so the filter works the same on SQLite and MySQL.
     *
     * @return array<int, int>
     */
    private function heavyUserIds(): array
    {
        return AiGeneration::query()
            ->select('user_id')
            ->where('created_at', '>=', now()->startOfDay())
            ->groupBy('user_id')
            ->havingRaw('count(*) >= ?', [(int) config('emailcv.ai.abuse_threshold_per_day')])
            ->pluck('user_id')
            ->all();
    }

    public function ban(BanUserRequest $request, User $user): RedirectResponse
    {
        $this->guardSelf($request, $user);

        $user->forceFill([
            'banned_at' => now(),
            'ban_reason' => $request->string('reason')->toString(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} has been suspended."]);

        return back();
    }

    public function unban(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['banned_at' => null, 'ban_reason' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} has been reinstated."]);

        return back();
    }

    /**
     * Add or remove credits by hand. Recorded as a ledger entry with the admin
     * who made it, rather than overwriting a number.
     */
    public function adjustCredits(Request $request, User $user, CreditLedger $credits): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:-100000', 'max:100000', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:200'],
        ], [
            'amount.not_in' => 'Enter a positive number to add credits, or a negative one to take them away.',
        ]);

        $credits->grant($user, CreditTransaction::ADMIN_ADJUSTMENT, (int) $validated['amount'], [
            'description' => $validated['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf(
                '%s credits %s %s. New balance: %d.',
                abs((int) $validated['amount']),
                $validated['amount'] > 0 ? 'added to' : 'taken from',
                $user->name,
                $credits->balance($user),
            ),
        ]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardSelf($request, $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account deleted.']);

        return back();
    }

    /**
     * An admin locking themselves out would leave the CMS unreachable.
     */
    private function guardSelf(Request $request, User $user): void
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot do that to your own account.');
    }
}
