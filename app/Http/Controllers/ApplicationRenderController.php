<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Profile;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Renders the live preview. Kept as JSON rather than an Inertia visit so the
 * editor can refresh the preview on every keystroke without a page transition.
 */
class ApplicationRenderController extends Controller
{
    public function __invoke(Request $request, Application $application, TemplateRenderer $renderer): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'company' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'field_values' => ['array'],
        ]);

        $application->fill([
            'company' => $validated['company'] ?? null,
            'position' => $validated['position'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
        ]);

        $template = $application->template;

        if (! $template) {
            return response()->json(['html' => '', 'subject' => '']);
        }

        $html = $renderer->render(
            $template,
            $request->user()->profile,
            $validated['field_values'] ?? [],
            $application
        );

        return response()->json([
            'html' => $html,
            'subject' => $this->subject($application, $request->user()->profile, $request->user()->name),
        ]);
    }

    private function subject(Application $application, ?Profile $profile, string $fallbackName): string
    {
        $name = $profile?->full_name ?: $fallbackName;

        return collect([
            'Application',
            $application->position ? 'for '.$application->position : null,
            $application->company ? '— '.$application->company : null,
            '| '.$name,
        ])->filter()->implode(' ');
    }
}
