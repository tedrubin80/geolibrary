<?php

namespace GEOOptimizer\Exceptions;

use Exception;

/**
 * Base exception class for GEO Optimizer
 */
class GEOException extends Exception
{
    protected $context = [];

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}