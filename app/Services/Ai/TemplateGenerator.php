<?php

namespace App\Services\Ai;

use App\Models\AiGeneration;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Templates\AccentPalette;
use App\Services\Templates\TemplateParser;
use App\Services\Templates\TemplateValidator;
use Illuminate\Support\Str;

/**
 * Designs a whole email template from a user's brief.
 *
 * Output is treated as hostile: it is sanitised, linted, parsed and test
 * rendered before it is allowed anywhere near the database, because the brief
 * is user-written and a promoted template eventually reaches every account.
 */
class TemplateGenerator
{
    public function __construct(
        private DeepSeekClient $client,
        private TemplateValidator $validator,
    ) {}

    public function generate(User $user, string $brief): GeneratedTemplate
    {
        $startedAt = microtime(true);
        $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        $repairNotes = null;

        // One repair pass: the model is told exactly what it broke and asked to
        // fix it. A rejected attempt must not cost the user an allowance.
        foreach ([1, 2] as $attempt) {
            try {
                $result = $this->client->json(
                    $this->systemPrompt(),
                    $this->userPrompt($brief, $repairNotes),
                    temperature: 0.8,
                );
            } catch (AiException $e) {
                $this->record($user, 'failed', $usage, $startedAt, $e->getMessage());

                throw $e;
            }

            foreach ($usage as $key => $value) {
                $usage[$key] = $value + ($result['usage'][$key] ?? 0);
            }

            $problems = [];
            $candidate = $this->build($result['content'], $brief, $problems);

            if ($candidate !== null) {
                return new GeneratedTemplate(
                    $candidate,
                    $this->record($user, 'success', $usage, $startedAt),
                );
            }

            $repairNotes = $problems;
        }

        $message = 'The AI could not produce a usable template this time. Try describing the look you want in different words.';

        $this->record($user, 'failed', $usage, $startedAt, $message.' Problems: '.implode(' ', $repairNotes));

        throw new AiException($message);
    }

    /**
     * Turn raw model output into a saved template, or explain why it cannot be.
     *
     * @param  array<string, mixed>  $content
     * @param  array<int, string>  $problems
     */
    private function build(array $content, string $brief, array &$problems): ?EmailTemplate
    {
        $html = trim((string) ($content['html'] ?? ''));

        if ($html === '') {
            $problems[] = 'You returned no html.';

            return null;
        }

        $accent = $this->accent($content['accent_color'] ?? null);
        $check = $this->validator->check($html, $accent);

        if (! $check->isUsable()) {
            foreach ($check->problems as $problem) {
                $problems[] = $problem;
            }

            return null;
        }

        $template = new EmailTemplate([
            'name' => Str::limit(trim((string) ($content['name'] ?? 'My template')), 60, ''),
            'description' => Str::limit(trim((string) ($content['description'] ?? '')), 400, ''),
            'accent_color' => $accent,
            'html' => $check->html,
            'fields' => $check->fields,
            'is_active' => true,
            'sort_order' => 100,
        ]);

        $template->brief = Str::limit($brief, 1000, '');

        return $template;
    }

    private function accent(mixed $value): string
    {
        $palette = (new AccentPalette)->for(is_string($value) ? $value : null);

        return $palette['accent'];
    }

    private function systemPrompt(): string
    {
        $profileTokens = implode(', ', TemplateParser::PROFILE_TOKENS);
        $applicationTokens = implode(', ', TemplateParser::APPLICATION_TOKENS);
        $paletteTokens = implode(', ', TemplateParser::TEMPLATE_TOKENS);

        return <<<PROMPT
        You design HTML email templates for job application emails. The applicant pastes the
        finished HTML into Gmail and sends it to a recruiter.

        ## Hard rules, in order of importance

        1. Layout is built ONLY from nested <table> elements with role="presentation".
           No flexbox, no grid, no CSS positioning, no floats.
        2. ALL styling goes in inline style="" attributes. Never emit <style>, <link>,
           <script>, <meta>, <head>, <form>, <svg> or HTML comments. They are stripped.
        3. No media queries. Make it fluid instead: width:100% with max-width:640px.
        4. Every <img> needs an alt attribute. Use no images except {{ photo_url }}.
        5. Use HTML entities or numeric character references for symbols and emoji,
           for example &middot; &bull; &#10024;. Never paste raw special characters.
        6. Every cell that contains text needs its own background-color, so dark mode
           cannot invert it into unreadable colours.
        7. Where you use a CSS gradient, also set a bgcolor attribute on the same cell.

        ## Token syntax

        - `{{ token }}` inserts one value.
        - `{{# token }}...{{ . }}...{{/ token }}` repeats a block once per list item.

        ## Tokens filled in automatically — use them, never invent alternatives

        - From the applicant's saved profile: {$profileTokens}
        - From the application being written: {$applicationTokens}
        - Colours derived from the accent you choose: {$paletteTokens}
          `accent` is the main colour, `accent_alt` a harmonious partner for gradients,
          `accent_dark` a darker shade, `accent_soft` a pale tint for panels, and
          `accent_contrast` is readable text on top of `accent`.

        ## Tokens you invent for the applicant to fill in

        Use snake_case. The name decides the input type, so follow these endings:
        - `..._paragraph`, or a name containing intro/body/closing/summary  -> a paragraph
        - a `{{# ... }}` block  -> a list, one item per line
        - anything else  -> a single short line

        Invent between four and eight of these.

        ## Repeating blocks

        Inside `{{# token }}...{{/ token }}` the ONLY thing that differs between items
        is `{{ . }}`. Never put another invented token inside a block — it would render
        the same value in every row. `{{ . }}` is always plain text: never use it as a
        URL, an image src, or a colour.

        Wrong:   {{# projects }}<td><img src="{{ . }}"><b>{{ project_name }}</b></td>{{/ projects }}
        Right:   {{# projects }}<tr><td style="background-color:#ffffff">{{ . }}</td></tr>{{/ projects }}

        ## Colour

        Style the design with the palette tokens, not hard-coded brand colours, so the
        accent can be changed later: {{ accent }}, {{ accent_alt }}, {{ accent_dark }},
        {{ accent_soft }} and {{ accent_contrast }}. Plain greys, white and black may be
        hard-coded. The template must use {{ accent }} at least once.

        ## The email must contain

        - a greeting addressed with {{ recipient_name }}
        - the role being applied for, using {{ position }} and {{ company }}
        - the applicant's {{ full_name }}
        - a sign-off with their contact details

        ## Output

        Return one JSON object:

        {
          "name": "Two or three words naming the design",
          "description": "One sentence on who this suits and when to pick it",
          "accent_color": "#RRGGBB",
          "html": "the full template"
        }

        The html value is a fragment: start at the outermost <table>, with no doctype
        and no <html> or <body> wrapper. Keep it under 60000 characters.
        PROMPT;
    }

    /**
     * @param  array<int, string>|null  $repairNotes
     */
    private function userPrompt(string $brief, ?array $repairNotes): string
    {
        $brief = Str::limit($brief, 1200, '');

        if ($repairNotes === null) {
            return <<<PROMPT
            Design a template for this brief:

            {$brief}

            Return the JSON object now.
            PROMPT;
        }

        $notes = implode("\n- ", $repairNotes);

        return <<<PROMPT
        Your previous attempt at this brief was rejected:

        {$brief}

        Problems found:
        - {$notes}

        Fix every one of them and return the corrected JSON object now.
        PROMPT;
    }

    /**
     * @param  array<string, int>  $usage
     */
    private function record(User $user, string $status, array $usage, float $startedAt, ?string $error = null): AiGeneration
    {
        return AiGeneration::create([
            'user_id' => $user->id,
            'kind' => 'template',
            'model' => $this->client->model(),
            'status' => $status,
            'error' => $error ? Str::limit($error, 500) : null,
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens' => $usage['total_tokens'] ?? 0,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'ip_address' => request()->ip(),
        ]);
    }
}
