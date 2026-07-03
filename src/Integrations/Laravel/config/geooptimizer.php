<?php

declare(strict_types=1);

return [
    'cache_enabled' => env('GEO_CACHE_ENABLED', false),
    'cache_ttl' => (int) env('GEO_CACHE_TTL', 3600),
    'cache' => [
        'adapter' => env('GEO_CACHE_ADAPTER', 'file'),
        'path' => env('GEO_CACHE_PATH', storage_path('framework/cache/geo')),
        'prefix' => env('GEO_CACHE_PREFIX', 'geo_'),
    ],
    'analysis' => [
        'min_word_count' => (int) env('GEO_MIN_WORD_COUNT', 300),
        'enable_readability' => (bool) env('GEO_ENABLE_READABILITY', true),
    ],
    'platforms' => [
        'openai' => ['api_key' => env('OPENAI_API_KEY', '')],
        'claude' => ['api_key' => env('ANTHROPIC_API_KEY', '')],
        'perplexity' => ['api_key' => env('PERPLEXITY_API_KEY', '')],
        'google' => ['api_key' => env('GOOGLE_AI_API_KEY', '')],
    ],
];
