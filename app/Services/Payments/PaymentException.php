<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Thrown when Xendit cannot be reached or rejects a request, so a checkout
 * failure surfaces as a clear message rather than a raw HTTP error.
 */
class PaymentException extends RuntimeException {}
