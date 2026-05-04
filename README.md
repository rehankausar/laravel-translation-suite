# Translation Services Package

A comprehensive Laravel package for multi-service translation with support for Google Translate, Azure Translator, DeepL, OpenAI, Yandex Translate, Amazon Translate, and Ollama.

## Features

- **Multiple Translation Services**: Supports 7 translation providers, including hosted APIs and local Ollama models
- **Unified Interface**: Single API for all services
- **Spatie Translatable Integration**: Works seamlessly with `spatie/laravel-translatable`
- **Plug-and-Play**: Easy service switching via configuration
- **Batch Translation**: Translate multiple texts efficiently
- **Model Translation**: Auto-translate model fields
- **Error Handling**: Robust error handling with standardized responses
- **Caching Support**: Reduce API calls and costs
- **Fallback Support**: Automatic fallback to alternative services

## Installation

### 1. Add to composer.json

Since this is a local package, add it to your main project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/translation-services"
        }
    ],
    "require": {
        "fastnet/translation-services": "*"
    }
}
```

### 2. Install the package

```bash
composer update fastnet/translation-services
```

### 3. Publish configuration

```bash
php artisan vendor:publish --tag=translation-services-config
```

### 4. Configure services

Edit `config/translation-services.php` or add to your `.env`:

```env
# Default service
TRANSLATION_SERVICE=google

# Google Translate
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=your-api-key

# DeepL
DEEPL_ENABLED=true
DEEPL_API_KEY=your-api-key
DEEPL_PRO=false

# Azure Translator
AZURE_TRANSLATE_ENABLED=true
AZURE_TRANSLATE_API_KEY=your-api-key
AZURE_TRANSLATE_REGION=global

# OpenAI
OPENAI_TRANSLATE_ENABLED=true
OPENAI_API_KEY=your-api-key
OPENAI_MODEL=gpt-3.5-turbo

# Yandex Translate
YANDEX_TRANSLATE_ENABLED=true
YANDEX_TRANSLATE_API_KEY=your-api-key
YANDEX_FOLDER_ID=your-folder-id

# Amazon Translate
AMAZON_TRANSLATE_ENABLED=true
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1

# Ollama (Local AI)
OLLAMA_TRANSLATE_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2:7b
OLLAMA_TIMEOUT=120
```

## Usage

### Basic Translation

```php
use Fastnet\TranslationServices\Facades\Translator;

// Translate text
$result = Translator::translate('Hello World', 'ar');

if ($result['success']) {
    echo $result['translated_text']; // مرحبا بالعالم
}
```

### Using Specific Service

```php
// Use Google Translate
$result = Translator::useService('google')
    ->translate('Hello World', 'es');

// Use DeepL
$result = Translator::useService('deepl')
    ->translate('Hello World', 'de');

// Use OpenAI
$result = Translator::useService('openai')
    ->translate('Hello World', 'fr');

// Use Ollama locally
$result = Translator::useService('ollama')
    ->translate('Hello World', 'ur');
```

### Batch Translation

```php
$texts = [
    'Hello World',
    'Good Morning',
    'Thank You'
];

$results = Translator::translateBatch($texts, 'ar');

foreach ($results as $result) {
    if ($result['success']) {
        echo $result['translated_text'] . "\n";
    }
}
```

### Model Translation (Spatie Translatable)

```php
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}

// Translate model fields
$product = Product::find(1);

$results = Translator::translateModel(
    model: $product,
    fields: ['name', 'description'],
    targetLanguage: 'ar',
    sourceLanguage: 'en',
    save: true
);

// Check results
foreach ($results as $field => $result) {
    if ($result['success']) {
        echo "{$field}: {$result['translated_text']}\n";
    }
}
```

### Advanced Model Translation

```php
// Translate to multiple languages
$product = Product::find(1);
$languages = ['ar', 'es', 'fr', 'de'];

foreach ($languages as $lang) {
    Translator::translateModel(
        $product,
        ['name', 'description'],
        $lang,
        'en'
    );
}

// Now you can use:
$product->getTranslation('name', 'ar');
$product->getTranslation('description', 'es');
```

### Using Without Facade

```php
use Fastnet\TranslationServices\TranslationManager;

$translator = new TranslationManager();

$result = $translator->translate('Hello World', 'ar');
```

### Dependency Injection

```php
use Fastnet\TranslationServices\TranslationManager;

class TranslationService
{
    public function __construct(
        private TranslationManager $translator
    ) {}

    public function translateProduct(Product $product, string $language)
    {
        return $this->translator->translateModel(
            $product,
            ['name', 'description'],
            $language
        );
    }
}
```

### Response Format

All translation methods return a standardized response:

```php
[
    'success' => true,
    'translated_text' => 'مرحبا بالعالم',
    'source_language' => 'en',
    'target_language' => 'ar',
    'service' => 'google',
    'metadata' => [
        'model' => 'nmt',
        'confidence' => 0.99
    ],
    'error' => null
]
```

### Error Response

```php
[
    'success' => false,
    'translated_text' => null,
    'source_language' => 'en',
    'target_language' => 'ar',
    'service' => 'google',
    'metadata' => [],
    'error' => [
        'message' => 'API key invalid',
        'code' => 401
    ]
]
```

### Check Available Services

```php
$services = Translator::getAvailableServices();

foreach ($services as $service) {
    echo "{$service['display_name']}: " .
         ($service['configured'] ? 'Ready' : 'Not configured') . "\n";
}
```

### Check Specific Service

```php
if (Translator::hasService('deepl')) {
    $result = Translator::useService('deepl')
        ->translate('Hello', 'de');
}
```

## Real-World Example

### Translating Product Catalog

```php
use App\Models\Product;
use Fastnet\TranslationServices\Facades\Translator;

class ProductTranslationService
{
    public function translateAllProducts(string $targetLanguage)
    {
        $products = Product::all();
        $results = [];

        foreach ($products as $product) {
            try {
                $result = Translator::useService('deepl')
                    ->translateModel(
                        $product,
                        ['name', 'description', 'features'],
                        $targetLanguage,
                        'en',
                        true
                    );

                $results[] = [
                    'product_id' => $product->id,
                    'success' => !empty(array_filter($result, fn($r) => $r['success'])),
                    'details' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'product_id' => $product->id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
```

### Command to Translate Models

```php
use Illuminate\Console\Command;
use Fastnet\TranslationServices\Facades\Translator;

class TranslateProductsCommand extends Command
{
    protected $signature = 'products:translate {language}';
    protected $description = 'Translate all products to specified language';

    public function handle()
    {
        $language = $this->argument('language');
        $products = Product::all();

        $this->withProgressBar($products, function ($product) use ($language) {
            Translator::translateModel(
                $product,
                ['name', 'description'],
                $language,
                'en'
            );
        });

        $this->info("\nTranslation completed!");
    }
}
```

## Service-Specific Features

### Google Translate
- Auto language detection
- Wide language support
- Fast and reliable

### DeepL
- High-quality translations
- Free and Pro tiers
- Excellent for European languages

### Azure Translator
- Microsoft's translation service
- Regional deployment
- High accuracy

### OpenAI (ChatGPT)
- Context-aware translations
- Natural language understanding
- Flexible model selection (GPT-3.5/GPT-4)

### Yandex Translate
- Good for Russian and Eastern European languages
- Cloud-based service

### Amazon Translate
- AWS integration
- Custom terminology support
- Scalable

### Ollama (Local AI)
- Local model-based translations through Ollama's OpenAI-compatible API
- No external translation API key required
- Configurable host, model, and timeout

## Language Codes

Common language codes supported by most services:

- `en` - English
- `ar` - Arabic
- `es` - Spanish
- `fr` - French
- `de` - German
- `it` - Italian
- `pt` - Portuguese
- `ru` - Russian
- `zh` - Chinese
- `ja` - Japanese
- `ko` - Korean
- `hi` - Hindi
- `ur` - Urdu

## Error Handling

```php
$result = Translator::translate('Hello', 'ar');

if (!$result['success']) {
    Log::error('Translation failed', [
        'service' => $result['service'],
        'error' => $result['error']['message']
    ]);

    // Use fallback text
    $translatedText = $originalText;
} else {
    $translatedText = $result['translated_text'];
}
```

## Testing

The package includes robust error handling and logging. To test:

```php
// Test service configuration
$service = Translator::driver('google');
if ($service->isConfigured()) {
    echo "Service is configured correctly\n";
}

// Test translation
$result = Translator::translate('Test', 'ar');
dd($result);
```

## Cost Optimization

1. **Enable Caching**: Cache translations to avoid repeated API calls
2. **Batch Translations**: Use `translateBatch()` when translating multiple texts
3. **Choose Right Service**: Different services have different pricing models
4. **Use Free Tiers**: DeepL offers free tier, OpenAI has lower costs for GPT-3.5

## Extending the Package

### Adding a New Service

1. Create service class in `src/Services/`
2. Implement `TranslationServiceInterface`
3. Extend `BaseTranslationService`
4. Add to `TranslationManager::driver()` method
5. Add configuration in `config/translation-services.php`

## Version History

**v1.1.0** - Ollama local AI translation support
- Added `ollama` translation driver
- Added Ollama environment and configuration options
- Updated service discovery and documentation for local AI translation

**v1.0.0** - Initial release
- 6 hosted translation services
- Spatie Translatable integration
- Batch translation
- Model translation

## License

MIT License

## Support

For issues and feature requests, please contact the development team.
