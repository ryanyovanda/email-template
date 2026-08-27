<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\Credits\CreditLedger;
use App\Services\Templates\TemplateLinter;
use App\Services\Templates\TemplateParser;
use App\Services\Templates\TemplatePreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        return Inertia::render('admin/templates/Index', [
            'templates' => EmailTemplate::query()
                ->withCount('applications')
                ->with('creator:id,name,email')
                ->when($filter === 'user', fn ($query) => $query->madeByUsers())
                ->when($filter === 'pending', fn ($query) => $query->madeByUsers()->where('visibility', 'private'))
                ->when($filter === 'global', fn ($query) => $query->global())
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (EmailTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'description' => $template->description,
                    'accent_color' => $template->accent_color,
                    'thumbnail_url' => $template->thumbnail_url,
                    'is_active' => $template->is_active,
                    'sort_order' => $template->sort_order,
                    'field_count' => count($template->fields ?? []),
                    'applications_count' => $template->applications_count,
                    'updated_at' => $template->updated_at?->diffForHumans(),
                    'visibility' => $template->visibility,
                    'origin' => $template->origin,
                    'is_user_made' => $template->isUserMade(),
                    'is_hand_written' => $template->isHandWritten(),
                    'brief' => $template->brief,
                    'author' => $template->isUserMade() ? $template->creator?->only(['id', 'name', 'email']) : null,
                    'terms_accepted' => $template->terms_accepted_at !== null,
                    'promoted_at' => $template->promoted_at?->diffForHumans(),
                ]),
            'filters' => ['filter' => $filter],
            'counts' => [
                'all' => EmailTemplate::count(),
                'user' => EmailTemplate::madeByUsers()->count(),
                'pending' => EmailTemplate::madeByUsers()->where('visibility', 'private')->count(),
                'global' => EmailTemplate::global()->count(),
            ],
        ]);
    }

    /**
     * Publish a user's design to the shared library. Permission for this is the
     * term they accepted when they generated it, so a template without that
     * acceptance on record is not publishable.
     */
    public function promote(Request $request, EmailTemplate $template, CreditLedger $credits): RedirectResponse
    {
        if ($template->isUserMade() && $template->terms_accepted_at === null) {
            return back()->withErrors([
                'template' => 'This design predates the sharing terms, so it cannot be published. Ask its author to generate it again.',
            ]);
        }

        $template->forceFill([
            'visibility' => 'global',
            'is_active' => true,
            'promoted_at' => now(),
            'promoted_by' => $request->user()->id,
        ])->save();

        $reward = null;

        if ($template->isUserMade() && $template->creator) {
            $reward = $credits->rewardPromotion($template->creator, $template->id);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $reward
                ? "“{$template->name}” is now in the shared library, and its author earned {$reward->amount} credits."
                : "“{$template->name}” is now in the shared library.",
        ]);

        return back();
    }

    /**
     * Withdraw a template from the shared library. Drafts already using it keep
     * working, since the template row itself is untouched.
     */
    public function demote(EmailTemplate $template): RedirectResponse
    {
        $template->forceFill([
            'visibility' => 'private',
            'promoted_at' => null,
            'promoted_by' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "“{$template->name}” is no longer offered to other users.",
        ]);

        return back();
    }

    public function create(): Response
    {
        return Inertia::render('admin/templates/Form', [
            'template' => null,
            'tokenReference' => $this->tokenReference(),
        ]);
    }

    public function edit(EmailTemplate $template): Response
    {
        return Inertia::render('admin/templates/Form', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'accent_color' => $template->accent_color,
                'thumbnail_url' => $template->thumbnail_url,
                'html' => $template->html,
                'fields' => $template->fields ?? [],
                'is_active' => $template->is_active,
                'sort_order' => $template->sort_order,
            ],
            'tokenReference' => $this->tokenReference(),
        ]);
    }

    public function store(StoreTemplateRequest $request, TemplateParser $parser): RedirectResponse
    {
        $template = new EmailTemplate;

        $this->persist($template, $request, $parser);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template created.']);

        return to_route('admin.templates.edit', $template);
    }

    public function update(StoreTemplateRequest $request, EmailTemplate $template, TemplateParser $parser): RedirectResponse
    {
        $this->persist($template, $request, $parser);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template updated.']);

        return back();
    }

    public function destroy(EmailTemplate $template): RedirectResponse
    {
        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template deleted. Drafts that used it keep their saved copy.']);

        return to_route('admin.templates.index');
    }

    /**
     * Live token detection, compatibility warnings and a sample render, used by
     * the template editor as the admin types.
     */
    public function analyse(Request $request, TemplateParser $parser, TemplateLinter $linter, TemplatePreview $preview): JsonResponse
    {
        $validated = $request->validate([
            'html' => ['required', 'string', 'max:200000'],
            'fields' => ['array'],
            'accent_color' => ['nullable', 'string', 'max:20'],
        ]);

        $fields = $parser->buildFields($validated['html'], $validated['fields'] ?? []);

        $candidate = new EmailTemplate([
            'html' => $validated['html'],
            'fields' => $fields,
            'accent_color' => $validated['accent_color'] ?? '#E86A33',
        ]);

        return response()->json([
            'fields' => $fields,
            'warnings' => $linter->lint($validated['html']),
            'html' => $preview->render($candidate),
        ]);
    }

    private function persist(EmailTemplate $template, StoreTemplateRequest $request, TemplateParser $parser): void
    {
        $data = $request->safe()->all();

        $template->fill([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            // The schema always follows the markup: tokens removed from the HTML
            // disappear from the form, and new ones appear with sensible defaults.
            'fields' => $parser->buildFields($data['html'], $data['fields'] ?? []),
        ]);

        $template->created_by ??= $request->user()->id;
        $template->save();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function tokenReference(): array
    {
        return [
            'profile' => TemplateParser::PROFILE_TOKENS,
            'application' => TemplateParser::APPLICATION_TOKENS,
            'template' => TemplateParser::TEMPLATE_TOKENS,
        ];
    }
}
