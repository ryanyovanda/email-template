<?php

namespace App\Services\Ai;

use App\Models\AiGeneration;
use App\Models\EmailTemplate;

/**
 * A freshly designed template together with the log entry that produced it, so
 * the caller can link the two without having to guess which row was the latest.
 */
class GeneratedTemplate
{
    public function __construct(
        public EmailTemplate $template,
        public AiGeneration $generation,
    ) {}
}
