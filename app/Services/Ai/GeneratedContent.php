<?php

namespace App\Services\Ai;

use App\Models\AiGeneration;

/**
 * Field values written by the AI, together with the log entry that produced
 * them, so a credit spend can point at exactly what it paid for.
 */
class GeneratedContent
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values,
        public AiGeneration $generation,
    ) {}
}
