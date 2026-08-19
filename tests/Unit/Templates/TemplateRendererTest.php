<?php

namespace Tests\Unit\Templates;

use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Profile;
use App\Services\Templates\TemplateParser;
use App\Services\Templates\TemplateRenderer;
use PHPUnit\Framework\TestCase;

class TemplateRendererTest extends TestCase
{
    private function template(string $html, string $accent = '#E86A33'): EmailTemplate
    {
        return new EmailTemplate([
            'html' => $html,
            'accent_color' => $accent,
            'fields' => (new TemplateParser)->buildFields($html),
        ]);
    }

    private function profile(array $overrides = []): Profile
    {
        return new Profile([
            'full_name' => 'Fajira Zenitha Purnama',
            'contact_email' => 'fajira@example.com',
            'phone' => '0851-5648-0171',
            'location' => 'Central Jakarta',
            'portfolio_url' => 'https://example.com/folio',
            ...$overrides,
        ]);
    }

    public function test_it_fills_profile_tokens(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<p>{{ full_name }} — {{ location }}</p>'),
            $this->profile(),
        );

        $this->assertSame('<p>Fajira Zenitha Purnama — Central Jakarta</p>', $html);
    }

    public function test_it_escapes_user_content(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<p>{{ intro }}</p>'),
            $this->profile(),
            ['intro' => '<script>alert(1)</script>'],
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_it_rejects_javascript_urls(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<a href="{{ portfolio_url }}">link</a>'),
            $this->profile(['portfolio_url' => 'javascript:alert(1)']),
        );

        $this->assertSame('<a href="">link</a>', $html);
    }

    public function test_it_assumes_https_for_a_bare_domain(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<a href="{{ portfolio_url }}">link</a>'),
            $this->profile(['portfolio_url' => 'example.com/me']),
        );

        $this->assertSame('<a href="https://example.com/me">link</a>', $html);
    }

    public function test_it_builds_a_whatsapp_link_from_an_indonesian_number(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<a href="{{ phone_link }}">chat</a>'),
            $this->profile(),
        );

        $this->assertSame('<a href="https://wa.me/6285156480171">chat</a>', $html);
    }

    public function test_it_repeats_block_tokens_for_each_list_item(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<div>{{# skills }}<span>{{ . }}</span>{{/ skills }}</div>'),
            $this->profile(),
            ['skills' => ['TNA', 'Curriculum Design']],
        );

        $this->assertSame('<div><span>TNA</span><span>Curriculum Design</span></div>', $html);
    }

    public function test_it_splits_a_newline_separated_list(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<div>{{# skills }}<span>{{ . }}</span>{{/ skills }}</div>'),
            $this->profile(),
            ['skills' => "TNA\nData Analysis"],
        );

        $this->assertSame('<div><span>TNA</span><span>Data Analysis</span></div>', $html);
    }

    public function test_it_drops_blocks_and_tokens_that_have_no_value(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<div>{{# skills }}<span>{{ . }}</span>{{/ skills }}</div><p>{{ unknown }}</p>'),
            $this->profile(),
        );

        $this->assertSame('<div></div><p></p>', $html);
    }

    public function test_it_resolves_the_accent_token_from_the_template(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<td style="color:{{ accent }}">x</td>', '#2563EB'),
            $this->profile(),
        );

        $this->assertStringContainsString('color:#2563EB', $html);
    }

    public function test_saved_values_cannot_override_profile_or_application_fields(): void
    {
        $application = new Application(['company' => 'Upsize Research']);

        $html = (new TemplateRenderer)->render(
            $this->template('<p>{{ full_name }} · {{ company }}</p>'),
            $this->profile(),
            ['full_name' => 'Someone Else', 'company' => 'Hacked Co'],
            $application,
        );

        $this->assertStringContainsString('Fajira Zenitha Purnama', $html);
        $this->assertStringContainsString('Upsize Research', $html);
        $this->assertStringNotContainsString('Hacked Co', $html);
    }

    public function test_it_turns_paragraph_breaks_into_markup(): void
    {
        $html = (new TemplateRenderer)->render(
            $this->template('<p>{{ intro }}</p>'),
            $this->profile(),
            ['intro' => "First line.\n\nSecond paragraph."],
        );

        $this->assertSame('<p>First line.<br><br>Second paragraph.</p>', $html);
    }
}
