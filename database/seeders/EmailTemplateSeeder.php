<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Services\Templates\TemplateParser;
use Illuminate\Database\Seeder;
use RuntimeException;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $parser = new TemplateParser;

        foreach ($this->templates() as $definition) {
            $path = __DIR__.'/templates/'.$definition['file'];
            $html = file_get_contents($path);

            if ($html === false) {
                throw new RuntimeException("Could not read the template file at {$path}.");
            }

            EmailTemplate::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'accent_color' => $definition['accent_color'],
                    'html' => $html,
                    'fields' => $parser->buildFields($html, $definition['fields']),
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'slug' => 'grayscale-accent',
                'name' => 'Grayscale Accent',
                'file' => 'grayscale-accent.html',
                'description' => 'Dark header with a photo, two highlight cards and a row of skill tags. Best when you have measurable achievements to lead with.',
                'accent_color' => '#E86A33',
                'sort_order' => 10,
                'fields' => [
                    ['token' => 'intro', 'label' => 'Opening line', 'type' => 'textarea', 'ai' => true, 'required' => true, 'help' => 'The one sentence that says which role you are applying for and why.'],
                    ['token' => 'highlight_one_title', 'label' => 'Highlight 1 — title', 'type' => 'text', 'ai' => true, 'help' => 'For example: Experience.'],
                    ['token' => 'highlight_one_detail', 'label' => 'Highlight 1 — detail', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'highlight_two_title', 'label' => 'Highlight 2 — title', 'type' => 'text', 'ai' => true, 'help' => 'For example: Education & Certification.'],
                    ['token' => 'highlight_two_detail', 'label' => 'Highlight 2 — detail', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'body_paragraph', 'label' => 'What you do', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'strengths_paragraph', 'label' => 'Strengths & motivation', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'skills', 'label' => 'Skill tags', 'type' => 'list', 'ai' => true, 'help' => 'One per line. Four to six reads best.'],
                    ['token' => 'availability', 'label' => 'Location & availability', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'closing', 'label' => 'Closing line', 'type' => 'textarea', 'ai' => true],
                ],
            ],
            [
                'slug' => 'clean-letter',
                'name' => 'Clean Letter',
                'file' => 'clean-letter.html',
                'description' => 'A traditional serif cover letter with no images. The safest choice for conservative industries and the most reliable across email clients.',
                'accent_color' => '#1F3A5F',
                'sort_order' => 20,
                'fields' => [
                    ['token' => 'opening_paragraph', 'label' => 'Opening paragraph', 'type' => 'textarea', 'ai' => true, 'required' => true],
                    ['token' => 'experience_paragraph', 'label' => 'Experience paragraph', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'fit_paragraph', 'label' => 'Why this role paragraph', 'type' => 'textarea', 'ai' => true],
                    ['token' => 'closing_paragraph', 'label' => 'Closing paragraph', 'type' => 'textarea', 'ai' => true],
                ],
            ],
            [
                'slug' => 'neon-portfolio',
                'name' => 'Neon Portfolio',
                'file' => 'neon-portfolio.html',
                'description' => 'Gradient hero, a stats strip, highlight cards and a portfolio button. The loudest layout here — built for design, motion, content and brand roles where a plain letter reads as timid.',
                'accent_color' => '#7C3AED',
                'sort_order' => 15,
                'fields' => [
                    ['token' => 'pitch', 'label' => 'Your pitch', 'type' => 'textarea', 'ai' => true, 'required' => true, 'help' => 'Two or three sentences on what you make and why this role fits.'],
                    ['token' => 'stat_one_value', 'label' => 'Stat 1 — number', 'type' => 'text', 'ai' => true, 'help' => 'Short and punchy, e.g. 4+ or 120.'],
                    ['token' => 'stat_one_label', 'label' => 'Stat 1 — caption', 'type' => 'text', 'ai' => true, 'help' => 'e.g. years designing.'],
                    ['token' => 'stat_two_value', 'label' => 'Stat 2 — number', 'type' => 'text', 'ai' => true],
                    ['token' => 'stat_two_label', 'label' => 'Stat 2 — caption', 'type' => 'text', 'ai' => true],
                    ['token' => 'stat_three_value', 'label' => 'Stat 3 — number', 'type' => 'text', 'ai' => true],
                    ['token' => 'stat_three_label', 'label' => 'Stat 3 — caption', 'type' => 'text', 'ai' => true],
                    ['token' => 'highlights', 'label' => 'What you would bring', 'type' => 'list', 'ai' => true, 'help' => 'One per line. Three or four reads best — each should point at something real from your CV.'],
                    ['token' => 'skills', 'label' => 'Toolkit', 'type' => 'list', 'ai' => true, 'help' => 'One per line. Tools and craft, e.g. Figma, After Effects, Art Direction.'],
                    ['token' => 'cta_label', 'label' => 'Button text', 'type' => 'text', 'ai' => false, 'default' => 'See my portfolio', 'help' => 'The button links to the portfolio URL on your profile.'],
                    ['token' => 'closing', 'label' => 'Closing line', 'type' => 'textarea', 'ai' => true],
                ],
            ],
            [
                'slug' => 'split-profile',
                'name' => 'Split Profile',
                'file' => 'split-profile.html',
                'description' => 'Coloured profile banner, a short pitch and a bulleted proof list. Good for design, marketing and product roles.',
                'accent_color' => '#2563EB',
                'sort_order' => 30,
                'fields' => [
                    ['token' => 'pitch', 'label' => 'Your pitch', 'type' => 'textarea', 'ai' => true, 'required' => true],
                    ['token' => 'proof_points', 'label' => 'Proof points', 'type' => 'list', 'ai' => true, 'help' => 'One per line. Each should name a real result from your CV.'],
                    ['token' => 'closing', 'label' => 'Closing line', 'type' => 'textarea', 'ai' => true],
                ],
            ],
        ];
    }
}
