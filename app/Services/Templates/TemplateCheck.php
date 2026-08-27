<?php

namespace App\Services\Templates;

/**
 * The verdict on a piece of candidate template markup.
 *
 * `problems` are blocking: they are worded as instructions, because the same
 * list is handed to a user fixing their own HTML and fed back to the model as
 * repair notes. `warnings` and `removed` are advisory — the template still
 * saves, but the author deserves to know what an email client (or the
 * sanitiser) will do to it.
 */
final class TemplateCheck
{
    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, string>  $problems
     * @param  array<int, string>  $removed
     * @param  array<int, array{level: string, message: string}>  $warnings
     */
    public function __construct(
        public readonly string $html,
        public readonly array $fields,
        public readonly array $problems,
        public readonly array $removed,
        public readonly array $warnings,
        public readonly string $preview,
    ) {}

    public function isUsable(): bool
    {
        return $this->problems === [];
    }

    /**
     * Tokens the applicant fills in themselves, which is what decides whether
     * a template is a form or just fixed text.
     *
     * @return array<int, array<string, mixed>>
     */
    public function contentFields(): array
    {
        return array_values(array_filter(
            $this->fields,
            fn (array $field): bool => ($field['source'] ?? '') === 'content'
        ));
    }
}
