<?php

namespace App\Services\Templates;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Profile;

/**
 * Substitutes user content into an admin-authored template.
 *
 * Everything coming from a user is escaped before it reaches the HTML, so a
 * pasted `<script>` or a broken tag can never damage the layout of the email
 * or the preview iframe.
 */
class TemplateRenderer
{
    /** URL schemes allowed to reach an href or src attribute. */
    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * @param  array<string, mixed>  $values
     */
    public function render(EmailTemplate $template, ?Profile $profile, array $values = [], ?Application $application = null): string
    {
        $map = $this->valueMap($template, $profile, $values, $application);

        $html = $this->renderBlocks($template->html, $map, $template);
        $html = $this->renderSimpleTokens($html, $map, $template);

        return $this->stripUnresolvedTokens($html);
    }

    /**
     * Every token the template can resolve, as raw (unescaped) values.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function valueMap(EmailTemplate $template, ?Profile $profile, array $values = [], ?Application $application = null): array
    {
        $map = (new AccentPalette)->for($template->accent_color);

        if ($profile) {
            $map += [
                'full_name' => $profile->full_name,
                'headline' => $profile->headline,
                'contact_email' => $profile->contact_email,
                'phone' => $profile->phone,
                'phone_link' => $profile->whatsappUrl(),
                'location' => $profile->location,
                'portfolio_url' => $profile->portfolio_url,
                'linkedin_url' => $profile->linkedin_url,
                'photo_url' => $profile->photo_url,
                'cv_url' => $profile->cv_url,
                'cv_filename' => $profile->cv_filename,
                'today' => now()->format('j F Y'),
            ];
        }

        if ($application) {
            $map['recipient_name'] = $application->recipient_name;
            $map['company'] = $application->company;
            $map['position'] = $application->position;
        }

        foreach ($values as $token => $value) {
            $token = strtolower((string) $token);

            // Profile- and application-backed tokens are authoritative; a stale
            // saved value must never shadow what the user just typed.
            if (TemplateParser::isSystemToken($token)) {
                continue;
            }

            if ($application && in_array($token, TemplateParser::APPLICATION_TOKENS, true)) {
                continue;
            }

            if (filled($value) || ! array_key_exists($token, $map)) {
                $map[$token] = $value;
            }
        }

        return $map;
    }

    /**
     * Expand `{{# token }}…{{/ token }}` once per list item.
     *
     * @param  array<string, mixed>  $map
     */
    private function renderBlocks(string $html, array $map, EmailTemplate $template): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*#\s*([a-z0-9_]+)\s*\}\}(.*?)\{\{\s*\/\s*\1\s*\}\}/is',
            function (array $matches) use ($map): string {
                $token = strtolower($matches[1]);
                $body = $matches[2];
                $items = $this->toList($map[$token] ?? null);

                if ($items === []) {
                    return '';
                }

                return implode('', array_map(
                    fn (string $item): string => str_replace(
                        ['{{ . }}', '{{.}}', '{{ item }}', '{{item}}'],
                        $this->escape($item, 'text'),
                        $body
                    ),
                    $items
                ));
            },
            $html
        );
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function renderSimpleTokens(string $html, array $map, EmailTemplate $template): string
    {
        $types = $this->fieldTypes($template);

        return (string) preg_replace_callback(
            '/\{\{\s*(?![#\/!])([a-z0-9_]+)\s*\}\}/i',
            function (array $matches) use ($map, $types): string {
                $token = strtolower($matches[1]);

                if (! array_key_exists($token, $map)) {
                    return '';
                }

                $value = $map[$token];

                if (is_array($value)) {
                    $value = implode(', ', $this->toList($value));
                }

                return $this->escape((string) $value, $types[$token] ?? 'text');
            },
            $html
        );
    }

    /**
     * @return array<string, string>
     */
    private function fieldTypes(EmailTemplate $template): array
    {
        $types = [];

        foreach ($template->fields ?? [] as $field) {
            if (isset($field['token'])) {
                $types[strtolower((string) $field['token'])] = (string) ($field['type'] ?? 'text');
            }
        }

        // Profile tokens carry fixed types regardless of the template schema.
        return [
            ...$types,
            'contact_email' => 'email',
            'phone_link' => 'url',
            'portfolio_url' => 'url',
            'linkedin_url' => 'url',
            'photo_url' => 'image',
            'cv_url' => 'url',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function toList(mixed $value): array
    {
        if (blank($value)) {
            return [];
        }

        $items = is_array($value)
            ? $value
            : (preg_split('/\r\n|\r|\n|,/', (string) $value) ?: []);

        return array_values(array_filter(array_map(
            fn ($item): string => trim((string) $item),
            $items
        ), fn (string $item): bool => $item !== ''));
    }

    private function escape(string $value, string $type): string
    {
        if (in_array($type, ['url', 'image', 'email'], true)) {
            return e($this->safeUrl($value, $type));
        }

        // Paragraph breaks survive as markup; single newlines become line breaks.
        $escaped = e($value);
        $escaped = preg_replace('/(\r\n|\r|\n){2,}/', '<br><br>', $escaped);

        return (string) preg_replace('/(\r\n|\r|\n)/', '<br>', (string) $escaped);
    }

    /**
     * Reject anything that is not a plain link, so `javascript:` and `data:`
     * payloads cannot reach an href or src.
     */
    private function safeUrl(string $value, string $type): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // A bare address in an email field is not a URL at all.
        if ($type === 'email' && ! preg_match('/^[a-z][a-z0-9+.\-]*:/i', $value)) {
            return $value;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $value, $matches) === 1) {
            // Anything carrying a scheme must carry one we allow — this is what
            // keeps `javascript:` and `data:` out of href and src attributes.
            return in_array(strtolower($matches[1]), self::SAFE_SCHEMES, true) ? $value : '';
        }

        // No scheme at all: a pasted bare domain such as `example.com/me`.
        return 'https://'.ltrim($value, '/');
    }

    /**
     * Tokens with no value at all are dropped rather than shipped to a recruiter.
     */
    private function stripUnresolvedTokens(string $html): string
    {
        $html = (string) preg_replace('/\{\{\s*#\s*[a-z0-9_]+\s*\}\}.*?\{\{\s*\/\s*[a-z0-9_]+\s*\}\}/is', '', $html);

        return (string) preg_replace('/\{\{\s*[^}]*\}\}/', '', $html);
    }
}
