<?php

declare(strict_types=1);

namespace GEOOptimizer\Contracts;

/**
 * Interface for content generators (llms.txt, schemas, etc.)
 */
interface GeneratorInterface
{
    /**
     * Generate content from data
     *
     * @param array<string, mixed> $data Input data
     * @return string|array<string, mixed> Generated content
     */
    public function generate(array $data): string|array;

    /**
     * Validate input data before generation
     *
     * @param array<string, mixed> $data Input data
     * @return bool
     */
    public function validate(array $data): bool;

    /**
     * Get supported output formats
     *
     * @return array<string>
     */
    public function getSupportedFormats(): array;
}
