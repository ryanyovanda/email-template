<?php

namespace Tests\Unit\Templates;

use App\Services\Templates\TemplateParser;
use PHPUnit\Framework\TestCase;

class TemplateParserTest extends TestCase
{
    public function test_it_detects_simple_and_block_tokens(): void
    {
        $fields = (new TemplateParser)->buildFields(
            '<p>{{ intro }}</p><div>{{# skills }}{{ . }}{{/ skills }}</div>'
        );

        $tokens = array_column($fields, 'token');

        $this->assertContains('intro', $tokens);
        $this->assertContains('skills', $tokens);
        $this->assertNotContains('.', $tokens);
    }

    public function test_it_labels_a_block_token_as_a_list(): void
    {
        $fields = (new TemplateParser)->buildFields('{{# proof_points }}{{ . }}{{/ proof_points }}');

        $this->assertSame('list', $fields[0]['type']);
        $this->assertSame('Proof Points', $fields[0]['label']);
        $this->assertTrue($fields[0]['ai']);
    }

    public function test_it_classifies_token_sources(): void
    {
        $fields = collect((new TemplateParser)->buildFields(
            '{{ full_name }}{{ company }}{{ intro }}{{ accent }}'
        ))->keyBy('token');

        $this->assertSame('profile', $fields['full_name']['source']);
        $this->assertSame('application', $fields['company']['source']);
        $this->assertSame('content', $fields['intro']['source']);
        $this->assertArrayNotHasKey('accent', $fields->all(), 'System tokens must not become form fields.');
    }

    public function test_it_infers_field_types_from_the_token_name(): void
    {
        $fields = collect((new TemplateParser)->buildFields(
            '{{ closing_paragraph }}{{ site_url }}{{ contact_email }}{{ highlight_title }}'
        ))->keyBy('token');

        $this->assertSame('textarea', $fields['closing_paragraph']['type']);
        $this->assertSame('url', $fields['site_url']['type']);
        $this->assertSame('email', $fields['contact_email']['type']);
        $this->assertSame('text', $fields['highlight_title']['type']);
    }

    public function test_it_keeps_admin_customisations_when_the_html_changes(): void
    {
        $parser = new TemplateParser;

        $original = $parser->buildFields('{{ intro }}');
        $original[0]['label'] = 'Opening line';
        $original[0]['ai'] = false;

        $updated = collect($parser->buildFields('{{ intro }}{{ closing }}', $original))->keyBy('token');

        $this->assertSame('Opening line', $updated['intro']['label']);
        $this->assertFalse($updated['intro']['ai']);
        $this->assertSame('Closing', $updated['closing']['label']);
    }

    public function test_removing_a_token_from_the_html_removes_the_field(): void
    {
        $parser = new TemplateParser;
        $original = $parser->buildFields('{{ intro }}{{ closing }}');

        $updated = array_column($parser->buildFields('{{ intro }}', $original), 'token');

        $this->assertSame(['intro'], $updated);
    }
}
