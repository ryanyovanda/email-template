<?php

namespace App\Services\Templates;

use Illuminate\Support\Str;

/**
 * Flags markup that Gmail silently discards. These are warnings, not errors —
 * an admin can still save a template that trips them.
 */
class TemplateLinter
{
    /**
     * @return array<int, array{level: string, message: string}>
     */
    public function lint(string $html): array
    {
        $warnings = [];

        $checks = [
            [
                'match' => fn (string $h): bool => (bool) preg_match('/<style[\s>]/i', $h),
                'level' => 'warning',
                'message' => 'Gmail strips <style> blocks in many contexts. Move these rules into inline style="" attributes.',
            ],
            [
                'match' => fn (string $h): bool => (bool) preg_match('/display\s*:\s*(flex|grid)/i', $h),
                'level' => 'warning',
                'message' => 'Gmail ignores flexbox and grid. Use nested <table> rows and cells for layout instead.',
            ],
            [
                'match' => fn (string $h): bool => (bool) preg_match('/position\s*:\s*(absolute|fixed|relative)/i', $h),
                'level' => 'warning',
                'message' => 'CSS positioning is unsupported in Gmail and will collapse the layout.',
            ],
            [
                'match' => fn (string $h): bool => (bool) preg_match('/<link[^>]+stylesheet/i', $h),
                'level' => 'error',
                'message' => 'External stylesheets never load in email clients. Inline the CSS.',
            ],
            [
                'match' => fn (string $h): bool => (bool) preg_match('/<script[\s>]/i', $h),
                'level' => 'error',
                'message' => 'Scripts are stripped by every email client and will get the message flagged.',
            ],
            [
                'match' => fn (string $h): bool => (bool) preg_match('/src\s*=\s*["\']\/(?!\/)/i', $h),
                'level' => 'error',
                'message' => 'Images use a relative path. Email clients need absolute https:// URLs.',
            ],
            [
                'match' => fn (string $h): bool => (bool) preg_match('/<img(?![^>]*\balt=)/i', $h),
                'level' => 'warning',
                'message' => 'An <img> has no alt text — Gmail blocks images by default, so alt text is what the recruiter sees first.',
            ],
            [
                'match' => fn (string $h): bool => Str::contains($h, ['Â·', 'â€', 'Ã©', 'â']),
                'level' => 'warning',
                'message' => 'The HTML contains mis-encoded characters (mojibake). Re-paste it as UTF-8, or replace them with HTML entities like &middot;.',
            ],
            [
                'match' => fn (string $h): bool => ! Str::contains(strtolower($h), '<table'),
                'level' => 'warning',
                'message' => 'No <table> found. Table-based layout is the only reliable structure across email clients.',
            ],
        ];

        foreach ($checks as $check) {
            if (($check['match'])($html)) {
                $warnings[] = ['level' => $check['level'], 'message' => $check['message']];
            }
        }

        return $warnings;
    }
}
