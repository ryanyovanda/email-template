<?php

namespace Tests\Unit\Templates;

use App\Services\Templates\AccentPalette;
use PHPUnit\Framework\TestCase;

class AccentPaletteTest extends TestCase
{
    public function test_it_returns_the_accent_unchanged(): void
    {
        $palette = (new AccentPalette)->for('#7C3AED');

        $this->assertSame('#7C3AED', $palette['accent']);
    }

    public function test_every_derived_colour_is_a_valid_hex(): void
    {
        foreach (['#7C3AED', '#E86A33', '#2563EB', '#FFE66D', '#000000', '#FFFFFF'] as $accent) {
            foreach ((new AccentPalette)->for($accent) as $key => $colour) {
                $this->assertMatchesRegularExpression(
                    '/^#[0-9A-F]{6}$/',
                    $colour,
                    "{$key} was not a valid hex colour for accent {$accent}."
                );
            }
        }
    }

    public function test_the_partner_colour_differs_from_the_accent(): void
    {
        $palette = (new AccentPalette)->for('#7C3AED');

        $this->assertNotSame($palette['accent'], $palette['accent_alt']);
    }

    public function test_the_soft_tint_is_lighter_and_the_shade_is_darker(): void
    {
        $palette = (new AccentPalette)->for('#7C3AED');

        $brightness = fn (string $hex): int => array_sum([
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ]);

        $this->assertGreaterThan($brightness($palette['accent']), $brightness($palette['accent_soft']));
        $this->assertLessThan($brightness($palette['accent']), $brightness($palette['accent_dark']));
    }

    public function test_button_text_flips_to_dark_on_a_light_accent(): void
    {
        $this->assertSame('#FFFFFF', (new AccentPalette)->for('#2563EB')['accent_contrast']);
        $this->assertSame('#1A1A1A', (new AccentPalette)->for('#FFE66D')['accent_contrast']);
    }

    public function test_a_shorthand_hex_is_expanded(): void
    {
        $this->assertSame(
            (new AccentPalette)->for('#0af')['accent'],
            (new AccentPalette)->for('#00AAFF')['accent'],
        );
    }

    public function test_an_unusable_accent_falls_back_instead_of_breaking_the_template(): void
    {
        foreach ([null, '', 'not-a-colour', '#12345', 'red'] as $value) {
            $this->assertSame(
                AccentPalette::FALLBACK,
                (new AccentPalette)->for($value)['accent'],
            );
        }
    }
}
