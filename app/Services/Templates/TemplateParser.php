<?php

namespace App\Services\Templates;

use Illuminate\Support\Str;

/**
 * Reads an admin-authored email template and works out which tokens it uses,
 * so the fill-in form can be generated instead of hand-written.
 *
 * Supported syntax:
 *   {{ token }}              a single value
 *   {{# token }}…{{/ token }} a block repeated once per item, with {{ . }} as the item
 */
class TemplateParser
{
    /**
     * Tokens resolved from the user's saved profile. These never appear as
     * inputs on the application form.
     */
    public const PROFILE_TOKENS = [
        'full_name',
        'headline',
        'contact_email',
        'phone',
        'phone_link',
        'location',
        'portfolio_url',
        'linkedin_url',
        'photo_url',
        'cv_url',
        'cv_filename',
        'today',
    ];

    /**
     * Tokens resolved from the template's own settings.
     */
    public const TEMPLATE_TOKENS = [
        'accent',
        'accent_dark',
        'accent_soft',
        'accent_alt',
        'accent_contrast',
    ];

    /**
     * Tokens filled from the application record itself rather than free text.
     */
    public const APPLICATION_TOKENS = [
        'recipient_name',
        'company',
        'position',
    ];

    public static function isProfileToken(string $token): bool
    {
        return in_array($token, self::PROFILE_TOKENS, true);
    }

    /**
     * Tokens the system resolves on its own, which never become form inputs.
     */
    public static function isSystemToken(string $token): bool
    {
        return self::isProfileToken($token) || in_array($token, self::TEMPLATE_TOKENS, true);
    }

    /**
     * Every distinct simple token used by the template, in order of appearance.
     *
     * @return array<int, string>
     */
    public function simpleTokens(string $html): array
    {
        preg_match_all('/\{\{\s*(?![#\/!])([a-z0-9_]+)\s*\}\}/i', $html, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    /**
     * Every distinct block (repeating list) token used by the template.
     *
     * @return array<int, string>
     */
    public function blockTokens(string $html): array
    {
        preg_match_all('/\{\{\s*#\s*([a-z0-9_]+)\s*\}\}/i', $html, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    /**
     * Build the field schema for a template, preserving any labels or types an
     * admin already customised.
     *
     * @param  array<int, array<string, mixed>>  $existing
     * @return array<int, array<string, mixed>>
     */
    public function buildFields(string $html, array $existing = []): array
    {
        $existingByToken = [];

        foreach ($existing as $field) {
            if (isset($field['token'])) {
                $existingByToken[strtolower((string) $field['token'])] = $field;
            }
        }

        $blocks = $this->blockTokens($html);

        // A block's inner {{ . }} is not a field, and neither is anything the
        // block repeats over, so simple tokens are collected from the outside.
        $simple = array_values(array_diff(
            $this->simpleTokens($html),
            $blocks,
            ['.', 'item'],
            self::TEMPLATE_TOKENS,
        ));

        $fields = [];

        foreach ([...$simple, ...$blocks] as $token) {
            $isBlock = in_array($token, $blocks, true);
            $previous = $existingByToken[$token] ?? null;

            $fields[] = [
                'token' => $token,
                'label' => $previous['label'] ?? $this->guessLabel($token),
                'type' => $previous['type'] ?? ($isBlock ? 'list' : $this->guessType($token)),
                'source' => $this->sourceFor($token),
                'ai' => $previous['ai'] ?? $this->guessAiWritable($token, $isBlock),
                'help' => $previous['help'] ?? null,
                'default' => $previous['default'] ?? null,
                'required' => $previous['required'] ?? false,
            ];
        }

        return $fields;
    }

    private function sourceFor(string $token): string
    {
        return match (true) {
            self::isProfileToken($token) => 'profile',
            in_array($token, self::TEMPLATE_TOKENS, true) => 'template',
            in_array($token, self::APPLICATION_TOKENS, true) => 'application',
            default => 'content',
        };
    }

    private function guessLabel(string $token): string
    {
        return Str::of($token)->replace('_', ' ')->title()->toString();
    }

    private function guessType(string $token): string
    {
        return match (true) {
            str_contains($token, 'email') => 'email',
            str_ends_with($token, '_url'), str_contains($token, 'link') => 'url',
            str_contains($token, 'photo'), str_contains($token, 'image'), str_contains($token, 'logo') => 'image',
            Str::contains($token, ['paragraph', 'body', 'intro', 'message', 'summary', 'closing', 'note', 'letter']) => 'textarea',
            default => 'text',
        };
    }

    private function guessAiWritable(string $token, bool $isBlock): bool
    {
        if (self::isProfileToken($token)) {
            return false;
        }

        if ($isBlock) {
            return true;
        }

        return in_array($this->guessType($token), ['textarea'], true)
            || in_array($token, self::APPLICATION_TOKENS, true);
    }
}
