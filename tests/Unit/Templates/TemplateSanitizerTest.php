<?php

namespace Tests\Unit\Templates;

use App\Services\Templates\TemplateSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TemplateSanitizerTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function payloads(): array
    {
        return [
            'script tag' => ['<table><tr><td>hi<script>alert(1)</script></td></tr></table>'],
            'uppercase script' => ['<SCRIPT>alert(1)</SCRIPT><p>keep</p>'],
            'img onerror' => ['<img src="x.jpg" alt="x" onerror="alert(1)">'],
            'td onclick' => ['<td onclick="steal()">x</td>'],
            'body onload' => ['<div OnLoad="alert(1)">x</div>'],
            'javascript href' => ['<a href="javascript:alert(1)">click</a>'],
            'vbscript href' => ['<a href="VBScript:msgbox(1)">click</a>'],
            'data uri image' => ['<img src="data:text/html;base64,PHN2Zz4=" alt="x">'],
            'style block' => ['<style>body{display:none}</style><p>keep</p>'],
            'iframe' => ['<iframe src="https://evil.test"></iframe><p>keep</p>'],
            'form harvest' => ['<form action="https://evil.test"><input name="pw"></form><p>keep</p>'],
            'svg onload' => ['<svg onload="alert(1)"></svg><p>keep</p>'],
            'css expression' => ['<div style="width:expression(alert(1))">x</div>'],
            'css import' => ['<div style="@import url(https://evil.test/a.css)">x</div>'],
            'css binding' => ['<div style="-moz-binding:url(https://evil.test/x.xml)">x</div>'],
            'tracking pixel' => ['<td style="background:url(http://track.test/p.gif)">x</td>'],
            'base tag' => ['<base href="https://evil.test/"><p>keep</p>'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.test"><p>keep</p>'],
            'comment smuggling' => ['<!-- <script>alert(1)</script> --><p>keep</p>'],
            'object embed' => ['<object data="evil.swf"></object><p>keep</p>'],
        ];
    }

    #[DataProvider('payloads')]
    public function test_it_strips_every_executable_payload(string $html): void
    {
        $output = (new TemplateSanitizer)->sanitize($html)['html'];

        $this->assertDoesNotMatchRegularExpression(
            '/<script|<iframe|<form|<style|<base|<meta|<svg|<object|on\w+\s*=|javascript:|vbscript:|expression\(|@import|-moz-binding|url\(/i',
            $output,
            'A payload survived sanitising: '.$output
        );
    }

    public function test_it_keeps_the_surrounding_content(): void
    {
        $output = (new TemplateSanitizer)->sanitize('<script>alert(1)</script><p>keep</p>')['html'];

        $this->assertStringContainsString('keep', $output);
    }

    public function test_it_preserves_tokens_exactly(): void
    {
        $html = '<table><tr><td style="color:{{ accent }}">'
            .'<img src="{{ photo_url }}" alt="{{ full_name }}">'
            .'<p>Dear {{ recipient_name }},</p>'
            .'<div>{{# skills }}<span>{{ . }}</span>{{/ skills }}</div>'
            .'</td></tr></table>';

        $output = (new TemplateSanitizer)->sanitize($html)['html'];

        foreach (['{{ accent }}', '{{ photo_url }}', '{{ full_name }}', '{{ recipient_name }}', '{{# skills }}', '{{ . }}', '{{/ skills }}'] as $token) {
            $this->assertStringContainsString($token, $output, "Token {$token} was mangled.");
        }

        // The classic failure is the DOM percent-encoding spaces inside attributes.
        $this->assertStringNotContainsString('%20', $output);
    }

    public function test_it_unwraps_unknown_tags_without_losing_their_text(): void
    {
        $output = (new TemplateSanitizer)->sanitize('<section><table><tr><td>deep text</td></tr></table></section>')['html'];

        $this->assertStringNotContainsString('<section', $output);
        $this->assertStringContainsString('deep text', $output);
        $this->assertStringContainsString('<table', $output);
    }

    public function test_it_keeps_legitimate_email_markup(): void
    {
        $html = '<table role="presentation" width="100%" cellpadding="0" bgcolor="#ffffff">'
            .'<tr><td align="center" style="padding:20px; background-color:#f5f5f5">'
            .'<a href="https://example.com" target="_blank">link</a>'
            .'<img src="https://example.com/a.jpg" alt="photo" width="60">'
            .'</td></tr></table>';

        $output = (new TemplateSanitizer)->sanitize($html)['html'];

        foreach (['role="presentation"', 'bgcolor="#ffffff"', 'align="center"', 'https://example.com', 'alt="photo"', 'background-color:#f5f5f5'] as $keep) {
            $this->assertStringContainsString($keep, $output);
        }
    }

    public function test_it_reports_what_it_removed(): void
    {
        $result = (new TemplateSanitizer)->sanitize('<p onclick="x()">hi</p><script>y()</script>');

        $this->assertNotEmpty($result['removed']);
    }

    public function test_empty_input_is_handled(): void
    {
        $this->assertSame('', (new TemplateSanitizer)->sanitize('   ')['html']);
    }
}
