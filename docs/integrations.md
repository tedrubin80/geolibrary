# Framework integrations

## Laravel

Register the service provider in `config/app.php` (Laravel 10 and earlier) or `bootstrap/providers.php` (Laravel 11+):

```php
GEOOptimizer\Integrations\Laravel\GEOOptimizerServiceProvider::class,
```

Publish configuration:

```bash
php artisan vendor:publish --tag=geooptimizer-config
```

Use the facade or container binding:

```php
use GEOOptimizer\Integrations\Laravel\Facades\GEOOptimizer;

$llmsTxt = GEOOptimizer::generateLLMSTxt([
    'business_name' => 'Acme Coffee',
    'description' => 'Specialty coffee shop.',
    'industry' => 'restaurant',
]);
```

Environment variables:

- `GEO_CACHE_ENABLED`
- `GEO_CACHE_TTL`
- `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `PERPLEXITY_API_KEY`, `GOOGLE_AI_API_KEY`

## Symfony

Register the bundle in `config/bundles.php`:

```php
GEOOptimizer\Integrations\Symfony\GEOOptimizerBundle::class => ['all' => true],
```

Configure in `config/packages/geo_optimizer.yaml`:

```yaml
geo_optimizer:
    cache_enabled: false
    cache_ttl: 3600
```

Inject the service:

```php
public function __construct(private \GEOOptimizer\GEOOptimizer $geoOptimizer)
{
}
```

## REST API

Start the built-in development server:

```bash
export GEO_API_KEY=your-secret
./bin/geo-api
```

Example requests:

```bash
curl http://127.0.0.1:8080/health
curl -H "X-API-Key: your-secret" http://127.0.0.1:8080/v1/industries
curl -X POST http://127.0.0.1:8080/v1/llms-txt \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret" \
  -d '{"business_name":"Acme","description":"Coffee shop","industry":"restaurant"}'
```

Routes:

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check (public) |
| GET | `/v1/industries` | List industries |
| POST | `/v1/optimize` | Full optimization |
| POST | `/v1/analyze` | Content analysis |
| POST | `/v1/llms-txt` | Generate llms.txt |
| POST | `/v1/schema` | Generate schema + JSON-LD |

## CLI additions

```bash
geo-optimizer industries
geo-optimizer report ./public --format json
```
