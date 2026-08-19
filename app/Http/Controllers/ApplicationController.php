<?php

namespace App\Http\Controllers;

use App\Http\Requests\Applications\StoreApplicationRequest;
use App\Http\Requests\Applications\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $applications = $request->user()->applications()
            ->with('template:id,name,accent_color')
            ->latest('updated_at')
            ->paginate(12)
            ->through(fn (Application $application): array => [
                'id' => $application->id,
                'title' => $application->title,
                'company' => $application->company,
                'position' => $application->position,
                'mode' => $application->mode,
                'template' => $application->template?->only(['id', 'name', 'accent_color']),
                'updated_at' => $application->updated_at?->diffForHumans(),
                'last_copied_at' => $application->last_copied_at?->diffForHumans(),
            ]);

        return Inertia::render('applications/Index', [
            'applications' => $applications,
            'remainingAi' => $request->user()->remainingAiGenerations(),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $template = EmailTemplate::active()->findOrFail($request->integer('email_template_id'));

        $application = $request->user()->applications()->create([
            'email_template_id' => $template->id,
            'title' => $request->string('title')->toString() ?: 'Untitled application',
            'mode' => 'manual',
            'field_values' => $template->defaultValues(),
        ]);

        return to_route('applications.edit', $application);
    }

    public function edit(Request $request, Application $application, TemplateRenderer $renderer): Response
    {
        $this->authorizeOwner($request, $application);

        $application->load('template');
        $profile = $request->user()->profile;
        $template = $application->template;

        return Inertia::render('applications/Editor', [
            'application' => [
                'id' => $application->id,
                'title' => $application->title,
                'company' => $application->company,
                'position' => $application->position,
                'recipient_name' => $application->recipient_name,
                'job_post' => $application->job_post,
                'mode' => $application->mode,
                'field_values' => (object) ($application->field_values ?? []),
            ],
            'template' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'accent_color' => $template->accent_color,
                'fields' => $template->userFields(),
            ] : null,
            'profile' => $profile?->only([
                'full_name', 'headline', 'contact_email', 'phone', 'location',
                'portfolio_url', 'linkedin_url', 'photo_url', 'cv_url', 'cv_filename',
            ]),
            'hasCvText' => (bool) $profile?->hasUsableCvText(),
            'initialHtml' => $template
                ? $renderer->render($template, $profile, $application->field_values ?? [], $application)
                : '',
            'remainingAi' => $request->user()->remainingAiGenerations(),
            'aiLimits' => [
                'daily' => $request->user()->dailyAiLimit(),
                'monthly' => $request->user()->monthlyAiLimit(),
            ],
            'extensionUrl' => config('emailcv.extension_url'),
        ]);
    }

    public function update(UpdateApplicationRequest $request, Application $application, TemplateRenderer $renderer): RedirectResponse
    {
        $this->authorizeOwner($request, $application);

        $application->fill($request->safe()->all());

        if ($application->template) {
            $application->rendered_html = $renderer->render(
                $application->template,
                $request->user()->profile,
                $application->field_values ?? [],
                $application
            );
        }

        $application->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft saved.']);

        return back();
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwner($request, $application);

        $application->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Application deleted.']);

        return to_route('applications.index');
    }

    private function authorizeOwner(Request $request, Application $application): void
    {
        abort_unless($application->user_id === $request->user()->id, 403);
    }
}
