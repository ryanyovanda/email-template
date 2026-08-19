<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Accounts that ran an unusual number of generations today, worth a look
     * before they exhaust the API budget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function heavyUsers(int $threshold): array
    {
        $rows = DB::table('ai_generations')
            ->select('user_id', DB::raw('count(*) as generations'), DB::raw('sum(total_tokens) as tokens'))
            ->where('created_at', '>=', now()->startOfDay())
            ->groupBy('user_id')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->orderByDesc('generations')
            ->limit(10)
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->map(fn (object $row): array => [
            'user' => $users->get($row->user_id)?->only(['id', 'name', 'email', 'banned_at']),
            'generations' => (int) $row->generations,
            'tokens' => (int) $row->tokens,
        ])->all();
    }

    public function __invoke(): Response
    {
        $abuseThreshold = (int) config('emailcv.ai.abuse_threshold_per_day');

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'bannedUsers' => User::whereNotNull('banned_at')->count(),
                'newUsersThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
                'applications' => Application::count(),
                'templates' => EmailTemplate::count(),
                'activeTemplates' => EmailTemplate::where('is_active', true)->count(),
                'generationsToday' => AiGeneration::where('created_at', '>=', now()->startOfDay())->count(),
                'generationsThisMonth' => AiGeneration::where('created_at', '>=', now()->startOfMonth())->count(),
                'tokensThisMonth' => (int) AiGeneration::where('created_at', '>=', now()->startOfMonth())->sum('total_tokens'),
                'failuresToday' => AiGeneration::where('status', 'failed')->where('created_at', '>=', now()->startOfDay())->count(),
            ],
            'abuseThreshold' => $abuseThreshold,
            'heavyUsers' => $this->heavyUsers($abuseThreshold),
        ]);
    }
}
