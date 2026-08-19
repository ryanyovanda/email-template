<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->profile;

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
                'aiRemaining' => $user->remainingAiGenerations(),
                'aiDailyLimit' => $user->dailyAiLimit(),
                'aiMonthlyLimit' => $user->monthlyAiLimit(),
                'aiUsedThisMonth' => $user->aiGenerationsThisMonth(),
            ],
            'recent' => $user->applications()
                ->with('template:id,name,accent_color')
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Application $application): array => [
                    'id' => $application->id,
                    'title' => $application->title,
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
