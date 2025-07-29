<?php

namespace GEOOptimizer\Exceptions;

/**
 * Validation exception class for GEO Optimizer
 */
class ValidationException extends GEOException
{
    public function __construct(string $message = 'Validation failed', int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
