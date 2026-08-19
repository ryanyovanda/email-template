<?php

namespace App\Services\Templates;

/**
 * Derives a small, always-harmonious palette from a template's single accent
 * colour, so a template can use gradients, tinted panels and readable button
 * text while an admin still controls the whole design from one colour picker.
 */
class AccentPalette
{
    public const FALLBACK = '#E86A33';

    /** Hue offset, in degrees, for the gradient partner colour. */
    private const ALT_HUE_SHIFT = 38;

    /**
     * Relative luminance above which dark text reads better than white.
     */
    private const LIGHT_THRESHOLD = 0.35;

    /**
     * @return array<string, string>
     */
    public function for(?string $accent): array
    {
        $rgb = $this->parse($accent) ?? $this->parse(self::FALLBACK);

        return [
            'accent' => $this->toHex($rgb),
            'accent_dark' => $this->toHex($this->mix($rgb, [0, 0, 0], 0.28)),
            'accent_soft' => $this->toHex($this->mix($rgb, [255, 255, 255], 0.86)),
            'accent_alt' => $this->toHex($this->shiftHue($rgb, self::ALT_HUE_SHIFT)),
            'accent_contrast' => $this->isLight($rgb) ? '#1A1A1A' : '#FFFFFF',
        ];
    }

    /**
     * @return array{int, int, int}|null
     */
    private function parse(?string $value): ?array
    {
        $hex = ltrim(trim((string) $value), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (preg_match('/^[0-9a-f]{6}$/i', $hex) !== 1) {
            return null;
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @param  array{int, int, int}  $rgb
     */
    private function toHex(array $rgb): string
    {
        return '#'.strtoupper(implode('', array_map(
            fn (int $channel): string => str_pad(dechex(max(0, min(255, $channel))), 2, '0', STR_PAD_LEFT),
            $rgb
        )));
    }

    /**
     * @param  array{int, int, int}  $rgb
     * @param  array{int, int, int}  $with
     * @return array{int, int, int}
     */
    private function mix(array $rgb, array $with, float $weight): array
    {
        return [
            (int) round($rgb[0] + ($with[0] - $rgb[0]) * $weight),
            (int) round($rgb[1] + ($with[1] - $rgb[1]) * $weight),
            (int) round($rgb[2] + ($with[2] - $rgb[2]) * $weight),
        ];
    }

    /**
     * Rotating the hue keeps the partner colour related to the original, so any
     * accent an admin picks still produces a gradient that looks deliberate.
     *
     * @param  array{int, int, int}  $rgb
     * @return array{int, int, int}
     */
    private function shiftHue(array $rgb, float $degrees): array
    {
        [$h, $s, $l] = $this->toHsl($rgb);

        return $this->toRgb(
            fmod($h + $degrees + 360, 360),
            min(1.0, $s * 1.05),
            $l
        );
    }

    /**
     * @param  array{int, int, int}  $rgb
     * @return array{float, float, float}
     */
    private function toHsl(array $rgb): array
    {
        [$r, $g, $b] = array_map(fn (int $channel): float => $channel / 255, $rgb);

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        $l = ($max + $min) / 2;

        if ($delta === 0.0) {
            return [0.0, 0.0, $l];
        }

        $s = $l > 0.5
            ? $delta / (2 - $max - $min)
            : $delta / ($max + $min);

        $h = match ($max) {
            $r => fmod(($g - $b) / $delta, 6),
            $g => (($b - $r) / $delta) + 2,
            default => (($r - $g) / $delta) + 4,
        };

        return [fmod($h * 60 + 360, 360), $s, $l];
    }

    /**
     * @return array{int, int, int}
     */
    private function toRgb(float $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0.0],
            $h < 120 => [$x, $c, 0.0],
            $h < 180 => [0.0, $c, $x],
            $h < 240 => [0.0, $x, $c],
            $h < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }

    /**
     * WCAG relative luminance, used to decide whether button text should be
     * white or near-black.
     *
     * @param  array{int, int, int}  $rgb
     */
    private function isLight(array $rgb): bool
    {
        $linear = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

        return $luminance > self::LIGHT_THRESHOLD;
    }
}
