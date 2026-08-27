<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CreditTransaction;
use App\Models\EmailTemplate;
use App\Services\Credits\CreditLedger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CreditLedger $credits): Response
    {
        $user = $request->user();
        $profile = $user->profile;
        $balance = $credits->balance($user);

        return Inertia::render('Dashboard', [
            'profile' => $profile?->only(['full_name', 'headline', 'photo_url', 'cv_filename', 'cv_parse_status']),
            'hasCvText' => (bool) $profile?->hasUsableCvText(),
            'checklist' => [
                'profile' => $profile !== null,
                'cv' => filled($profile?->cv_url),
                'cvText' => (bool) $profile?->hasUsableCvText(),
                'firstApplication' => $user->applications()->exists(),
                'extension' => $user->applications()->whereNotNull('last_copied_at')->exists(),
            ],
            'stats' => [
                'applications' => $user->applications()->count(),
                'sent' => $user->applications()->whereNotNull('last_copied_at')->count(),
                'credits' => $balance,
                'monthlyGrant' => (int) config('emailcv.credits.monthly_grant'),
                'draftPrice' => $credits->priceOf(CreditTransaction::APPLICATION_DRAFT),
                'templatePrice' => $credits->priceOf(CreditTransaction::TEMPLATE_DESIGN),
                'draftsAffordable' => intdiv($balance, max(1, $credits->priceOf(CreditTransaction::APPLICATION_DRAFT))),
            ],
            'creditHistory' => collect($credits->history($user, 6))
                ->map(fn (CreditTransaction $row): array => [
                    'id' => $row->id,
                    'amount' => $row->amount,
                    'label' => $row->label(),
                    'description' => $row->description,
                    'at' => $row->created_at?->diffForHumans(),
                ]),
            'recent' => $user->applications()
                ->with('template:id,name,accent_color')
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Application $application): array => [
                    'id' => $application->id,
                    'title' => $application->displayName(),
                    'company' => $application->company,
                    'position' => $application->position,
                    'template' => $application->template?->only(['name', 'accent_color']),
                    'updated_at' => $application->updated_at?->diffForHumans(),
                ]),
            'templateCount' => EmailTemplate::active()->count(),
            'extensionUrl' => config('emailcv.extension_url'),
        ]);
    }
}
