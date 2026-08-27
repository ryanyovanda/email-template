<?php

namespace App\Services\Templates;

use App\Models\EmailTemplate;
use App\Models\Profile;

/**
 * The single gate every non-admin template has to pass, whether a language
 * model wrote it or a user pasted it in by hand.
 *
 * Both paths need identical rules: a template that reaches the editor missing
 * {{ position }}, or repeating the same value down every row of a list, is
 * equally broken however it got there. Keeping the rules here is also what lets
 * the generator turn a rejection into repair notes and the builder turn the
 * same rejection into a checklist on screen.
 */
class TemplateValidator
{
    /**
     * Roughly a very long email. Past this the editor's live preview starts to
     * cost more than the design is worth.
     */
    public const MAX_HTML_BYTES = 60000;

    /** Below this the template is mostly fixed text and not worth a slot. */
    public const MIN_CONTENT_TOKENS = 3;

    public function __construct(
        private TemplateSanitizer $sanitizer,
        private TemplateParser $parser,
        private TemplateLinter $linter,
        private TemplatePreview $preview,
    ) {}

    /**
     * Sanitise the markup, then decide whether what survived is usable.
     *
     * The profile is optional and only affects the preview: a user building a
     * template sees their own name and photo in it, while the generator's smoke
     * test uses stand-ins.
     */
    public function check(string $html, ?string $accentColor = null, ?Profile $profile = null): TemplateCheck
    {
        $html = trim($html);

        if ($html === '') {
            return $this->rejected('There is no HTML here yet.');
        }

        if (strlen($html) > self::MAX_HTML_BYTES) {
            return $this->rejected(sprintf(
                'The HTML is %s characters. Keep it under %s.',
                number_format(strlen($html)),
                number_format(self::MAX_HTML_BYTES),
            ));
        }

        $sanitized = $this->sanitizer->sanitize($html);
        $html = $sanitized['html'];
        $removed = $sanitized['removed'];

        if (! str_contains(strtolower($html), '<table')) {
            return $this->rejected(
                'The layout must be built from <table> elements. Nothing usable survived filtering.',
                $removed,
            );
        }

        $warnings = $this->linter->lint($html);
        $problems = [];

        foreach ($warnings as $warning) {
            if ($warning['level'] === 'error') {
                $problems[] = $warning['message'];
            }
        }

        $fields = $this->parser->buildFields($html);
        $content = array_filter($fields, fn (array $field): bool => ($field['source'] ?? '') === 'content');

        if (count($content) < self::MIN_CONTENT_TOKENS) {
            $problems[] = sprintf(
                'The template only gives the applicant %d thing(s) to fill in. Add invented tokens until there are at least %d, so the email is not mostly fixed text.',
                count($content),
                self::MIN_CONTENT_TOKENS,
            );
        }

        foreach (self::requiredTokens() as $token => $explanation) {
            if (! $this->usesToken($html, $token)) {
                $problems[] = $explanation;
            }
        }

        foreach ($this->brokenBlocks($html) as $problem) {
            $problems[] = $problem;
        }

        // A template that throws while rendering would break the editor for
        // good, so it is rendered here rather than discovered later.
        $preview = '';

        try {
            $preview = $this->preview->render($this->candidate($html, $fields, $accentColor), $profile);
        } catch (\Throwable $e) {
            $problems[] = 'The template failed to render: '.$e->getMessage();
        }

        if ($preview !== '' && trim(strip_tags($preview)) === '') {
            $problems[] = 'The template rendered to an empty message.';
        }

        return new TemplateCheck($html, $fields, $problems, $removed, $warnings, $preview);
    }

    /**
     * Tokens without which the template cannot do its job, mapped to the
     * instruction that fixes them.
     *
     * @return array<string, string>
     */
    public static function requiredTokens(): array
    {
        return [
            'full_name' => 'The template must show the applicant name with {{ full_name }}.',
            'recipient_name' => 'The template must greet the recruiter using {{ recipient_name }}.',
            'accent' => 'Colour the design with the palette tokens. Use {{ accent }} instead of a hard-coded hex value.',
            'position' => 'The template must state the role using {{ position }}.',
            'company' => 'The template must name the employer using {{ company }}.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function candidate(string $html, array $fields, ?string $accentColor): EmailTemplate
    {
        return new EmailTemplate([
            'html' => $html,
            'fields' => $fields,
            'accent_color' => $accentColor ?: AccentPalette::FALLBACK,
        ]);
    }

    /**
     * @param  array<int, string>  $removed
     */
    private function rejected(string $problem, array $removed = []): TemplateCheck
    {
        return new TemplateCheck('', [], [$problem], $removed, [], '');
    }

    private function usesToken(string $html, string $token): bool
    {
        return preg_match('/\{\{\s*'.preg_quote($token, '/').'\s*\}\}/', $html) === 1;
    }

    /**
     * A repeating block renders identical markup per item, so any other invented
     * token inside it repeats the same value down every row. Catching it here is
     * the difference between a template that looks right and one that quietly
     * prints the first project three times.
     *
     * @return array<int, string>
     */
    private function brokenBlocks(string $html): array
    {
        preg_match_all('/\{\{\s*#\s*([a-z0-9_]+)\s*\}\}(.*?)\{\{\s*\/\s*\1\s*\}\}/is', $html, $matches, PREG_SET_ORDER);

        $problems = [];

        foreach ($matches as [, $name, $body]) {
            preg_match_all('/\{\{\s*(?![#\/])([a-z0-9_.]+)\s*\}\}/i', $body, $inner);

            $offenders = array_values(array_filter(
                array_unique($inner[1]),
                fn (string $token): bool => $token !== '.'
                    && $token !== 'item'
                    && ! TemplateParser::isSystemToken(strtolower($token))
            ));

            if ($offenders !== []) {
                $problems[] = sprintf(
                    'The {{# %s }} block contains %s. Inside a block only {{ . }} may vary — move those tokens outside it.',
                    $name,
                    implode(', ', array_map(fn (string $t): string => '{{ '.$t.' }}', $offenders))
                );
            }

            if (preg_match('/(src|href)\s*=\s*["\']?\s*\{\{\s*\.\s*\}\}/i', $body) === 1) {
                $problems[] = sprintf(
                    'The {{# %s }} block uses {{ . }} as a URL. List items are plain text the applicant types.',
                    $name
                );
            }
        }

        return $problems;
    }
}
