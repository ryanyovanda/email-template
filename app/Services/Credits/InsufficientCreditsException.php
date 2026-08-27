<?php

namespace App\Services\Credits;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct(
        public readonly int $required,
        public readonly int $balance,
    ) {
        parent::__construct(sprintf(
            'This needs %d credits and you have %d.',
            $required,
            $balance,
        ));
    }
}
