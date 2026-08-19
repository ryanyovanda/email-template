<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTemplateRequest;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Services\Templates\TemplateLinter;
use App\Services\Templates\TemplateParser;
use App\Services\Templates\TemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/templates/Index', [
            'templates' => EmailTemplate::query()
                ->withCount('applications')
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
                ]),
        ]);
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
    public function analyse(Request $request, TemplateParser $parser, TemplateLinter $linter, TemplateRenderer $renderer): JsonResponse
    {
        $validated = $request->validate([
            'html' => ['required', 'string', 'max:200000'],
            'fields' => ['array'],
            'accent_color' => ['nullable', 'string', 'max:20'],
        ]);

        $fields = $parser->buildFields($validated['html'], $validated['fields'] ?? []);

        $preview = new EmailTemplate([
            'html' => $validated['html'],
            'fields' => $fields,
            'accent_color' => $validated['accent_color'] ?? '#E86A33',
        ]);

        return response()->json([
            'fields' => $fields,
            'warnings' => $linter->lint($validated['html']),
            'html' => $renderer->render($preview, $this->sampleProfile(), $this->sampleValues($fields)),
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

    private function sampleProfile(): Profile
    {
        return new Profile([
            'full_name' => 'Fajira Zenitha Purnama',
            'headline' => 'Learning & Development Specialist',
            'contact_email' => 'fajira@example.com',
            'phone' => '0851-5648-0171',
            'location' => 'Central Jakarta',
            'portfolio_url' => 'https://example.com/portfolio',
            'linkedin_url' => 'https://linkedin.com/in/example',
            'photo_url' => 'https://res.cloudinary.com/demo/image/upload/w_120,h_120,c_fill,g_face,r_max/face_left.png',
            'cv_url' => 'https://example.com/cv.pdf',
            'cv_filename' => 'cv.pdf',
        ]);
    }

    /**
     * Placeholder copy so an admin can see the layout before any user exists.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function sampleValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $token = (string) $field['token'];

            if (TemplateParser::isSystemToken($token)) {
                continue;
            }

            $values[$token] = match ($field['type']) {
                'list' => ['TNA', 'Curriculum Design', 'Project Management', 'Data Analysis'],
                'textarea' => 'Sample paragraph showing how this block reads at a realistic length. Replace it with the copy your applicants will actually write, or let the AI draft it from their CV.',
                'url' => 'https://example.com',
                'email' => 'someone@example.com',
                'image' => 'https://res.cloudinary.com/demo/image/upload/w_120,h_120,c_fill/sample.jpg',
                default => match ($token) {
                    'recipient_name' => 'Riko',
                    'company' => 'Upsize Research',
                    'position' => 'Finance, People & General Affairs, Senior Associate',
                    default => 'Sample '.str_replace('_', ' ', $token),
                },
            };
        }

        return $values;
    }
}
