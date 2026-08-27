<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CreditTransaction;
use App\Services\Ai\AiException;
use App\Services\Ai\ApplicationContentGenerator;
use App\Services\Credits\CreditLedger;
use App\Services\Templates\TemplateParser;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AiDraftController extends Controller
{
    public function store(
        Request $request,
        Application $application,
        ApplicationContentGenerator $generator,
        TemplateRenderer $renderer,
        CreditLedger $credits,
    ): JsonResponse {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'job_post' => ['required', 'string', 'min:80', 'max:20000'],
            'company' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
        ], [
            'job_post.required' => 'Paste the job posting so the AI knows what to write for.',
            'job_post.min' => 'That job posting is too short to work from — paste the full posting.',
        ]);

        $user = $request->user();
        $profile = $user->profile;
        $template = $application->template;

        if (! $template) {
            return response()->json(['message' => 'This application no longer has a template.'], 422);
        }

        if (! $profile?->hasUsableCvText()) {
            return response()->json([
                'message' => 'We need your CV text before the AI can write anything. Upload a text-based CV or paste it into your profile.',
            ], 422);
        }

        $price = $credits->priceOf(CreditTransaction::APPLICATION_DRAFT);

        if (! $credits->canAfford($user, CreditTransaction::APPLICATION_DRAFT)) {
            return response()->json([
                'message' => sprintf(
                    'Writing this draft costs %d credits and you have %d. Your allowance tops up at the start of next month — until then you can still fill the template in yourself.',
                    $price,
                    $credits->balance($user),
                ),
            ], 402);
        }

        $rateKey = 'ai-draft:'.$user->id;

        if (RateLimiter::tooManyAttempts($rateKey, (int) config('emailcv.ai.rate_limit_per_minute'))) {
            return response()->json([
                'message' => 'Slow down a moment — you can run another generation in '.RateLimiter::availableIn($rateKey).' seconds.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);

        $application->fill($validated);
        $application->mode = 'ai';

        try {
            $generated = $generator->generate($user, $application, $template, $profile);
        } catch (AiException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $values = $generated->values;

        // Company, role and hiring contact belong to the application record, not
        // to the template content, and never overwrite what the user typed.
        foreach (TemplateParser::APPLICATION_TOKENS as $token) {
            if (! array_key_exists($token, $values)) {
                continue;
            }

            if (blank($application->{$token})) {
                $application->{$token} = $values[$token];
            }

            unset($values[$token]);
        }

        // AI output is a starting point layered over anything already filled in.
        $merged = [...($application->field_values ?? []), ...$values];

        $application->field_values = $merged;
        $application->last_generated_at = now();
        $application->rendered_html = $renderer->render($template, $profile, $merged, $application);
        $application->save();

        // Charged only now that the draft exists, so a failed call is free.
        $credits->spend($user, CreditTransaction::APPLICATION_DRAFT, [
            'ai_generation_id' => $generated->generation->id,
            'description' => $application->displayName(),
        ]);

        return response()->json([
            'field_values' => (object) $merged,
            'application' => $application->only(['company', 'position', 'recipient_name']),
            'html' => $application->rendered_html,
            'credits' => $credits->balance($user),
            'message' => 'Draft written. Review every line before you send it.',
        ]);
    }
}
