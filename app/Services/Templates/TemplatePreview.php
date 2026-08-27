<?php

namespace App\Services\Templates;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Profile;

/**
 * Renders a template with stand-in content, so a design can be judged before
 * anyone commits to using it.
 *
 * Where a real profile is supplied its values win, and only the gaps are filled
 * with samples — a user browsing the gallery sees their own name and photo in
 * every layout rather than a stranger's.
 */
class TemplatePreview
{
    public function __construct(private TemplateRenderer $renderer) {}

    public function render(EmailTemplate $template, ?Profile $profile = null): string
    {
        return $this->renderer->render(
            $template,
            $this->profile($profile),
            $this->sampleValues($template->userFields()),
            $this->application(),
        );
    }

    /**
     * A profile that is complete enough to fill any template, preferring the
     * real one field by field.
     */
    public function profile(?Profile $real = null): Profile
    {
        $sample = [
            'full_name' => 'Fajira Zenitha Purnama',
            'headline' => 'Learning & Development Specialist',
            'contact_email' => 'fajira@example.com',
            'phone' => '0851-5648-0171',
            'location' => 'Central Jakarta',
            'portfolio_url' => 'https://example.com/portfolio',
            'linkedin_url' => 'https://linkedin.com/in/example',
            'photo_url' => 'https://res.cloudinary.com/demo/image/upload/w_144,h_144,c_fill,g_face,r_max/face_left.png',
            'cv_url' => 'https://example.com/cv.pdf',
            'cv_filename' => 'cv.pdf',
        ];

        if ($real) {
            foreach ($sample as $key => $fallback) {
                if (filled($real->{$key})) {
                    $sample[$key] = $real->{$key};
                }
            }
        }

        return new Profile($sample);
    }

    public function application(): Application
    {
        return new Application([
            'recipient_name' => 'Riko',
            'company' => 'Upsize Research',
            'position' => 'Senior Associate',
        ]);
    }

    /**
     * Placeholder copy at a realistic length, so the preview shows how the
     * layout behaves rather than how it looks empty.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public function sampleValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $token = (string) ($field['token'] ?? '');

            if ($token === '' || TemplateParser::isSystemToken($token)) {
                continue;
            }

            if (filled($field['default'] ?? null)) {
                $values[$token] = (string) $field['default'];

                continue;
            }

            $values[$token] = match ($field['type'] ?? 'text') {
                'list' => ['TNA', 'Curriculum Design', 'Project Management', 'Data Analysis'],
                'textarea' => 'Sample paragraph showing how this block reads at a realistic length. Replace it with the copy you actually want, or let the AI draft it from your CV.',
                'url' => 'https://example.com',
                'email' => 'someone@example.com',
                'image' => 'https://res.cloudinary.com/demo/image/upload/w_120,h_120,c_fill/sample.jpg',
                default => $this->sampleLine($token),
            };
        }

        return $values;
    }

    private function sampleLine(string $token): string
    {
        return match ($token) {
            'recipient_name' => 'Riko',
            'company' => 'Upsize Research',
            'position' => 'Senior Associate',
            default => str_ends_with($token, '_value')
                ? '4+'
                : 'Sample '.str_replace('_', ' ', $token),
        };
    }
}
