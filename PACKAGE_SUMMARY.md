# Translation Services Package - Summary

## Overview

A comprehensive, production-ready Laravel translation package supporting 7 translation providers with seamless Spatie Translatable integration, including hosted APIs and local Ollama models.

## Package Structure

```
packages/translation-services/
├── src/
│   ├── Contracts/
│   │   └── TranslationServiceInterface.php    # Main contract
│   ├── Services/
│   │   ├── BaseTranslationService.php         # Base class with common functionality
│   │   ├── GoogleTranslateService.php         # Google Translate API
│   │   ├── DeepLService.php                   # DeepL API
│   │   ├── AzureTranslateService.php          # Microsoft Azure Translator
│   │   ├── OpenAIService.php                  # OpenAI ChatGPT
│   │   ├── YandexTranslateService.php         # Yandex Translate
│   │   └── AmazonTranslateService.php         # Amazon Translate
│   ├── Facades/
│   │   └── Translator.php                     # Laravel Facade
│   ├── TranslationManager.php                 # Main orchestrator class
│   └── TranslationServiceProvider.php         # Laravel service provider
├── config/
│   └── translation-services.php               # Configuration file
├── composer.json                              # Package dependencies
├── README.md                                  # Full documentation
├── EXAMPLES.md                                # Usage examples
├── INTEGRATION_GUIDE.md                       # Integration guide
└── .env.example                               # Environment variables example
```

The services directory includes `OllamaService.php` for local AI translation through Ollama's OpenAI-compatible chat API.

## Supported Services

| Service | Quality | Speed | Cost | Best For |
|---------|---------|-------|------|----------|
| **Google Translate** | Good | Fast | Low | General content, high volume |
| **DeepL** | Excellent | Fast | Medium | Marketing, professional content |
| **Azure Translator** | Very Good | Fast | Medium | Enterprise, technical content |
| **OpenAI (ChatGPT)** | Excellent | Medium | Medium-High | Creative, context-aware content |
| **Yandex** | Good | Fast | Low | Russian, Eastern European languages |
| **Amazon Translate** | Very Good | Fast | Low | AWS integration, scalable |
| **Ollama** | Model-dependent | Medium | Local compute | Private/local AI translation |

## Key Features

### 1. Unified Interface
All services implement the same interface, making switching seamless:

```php
// Same code works for all services
Translator::translate('Hello World', 'ar');
Translator::useService('deepl')->translate('Hello World', 'ar');
Translator::useService('openai')->translate('Hello World', 'ar');
Translator::useService('ollama')->translate('Hello World', 'ar');
```

### 2. Model Translation
Direct integration with Spatie Translatable:

```php
Translator::translateModel(
    $product,
    ['name', 'description'],
    'ar',
    'en',
    true // auto-save
);
```

### 3. Batch Translation
Efficient translation of multiple texts:

```php
$results = Translator::translateBatch(
    ['Hello', 'Goodbye', 'Thank you'],
    'ar'
);
```

### 4. Standardized Response
All services return the same format:

```php
[
    'success' => true,
    'translated_text' => 'مرحبا بالعالم',
    'source_language' => 'en',
    'target_language' => 'ar',
    'service' => 'google',
    'metadata' => [...],
    'error' => null
]
```

### 5. Error Handling
Robust error handling with detailed logging:

```php
if (!$result['success']) {
    Log::error('Translation failed', $result['error']);
    // Use fallback or original text
}
```

## Installation

### Quick Start

1. **Add to composer.json:**
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

2. **Install:**
```bash
composer update fastnet/translation-services
```

3. **Publish config:**
```bash
php artisan vendor:publish --tag=translation-services-config
```

4. **Configure .env:**
```env
TRANSLATION_SERVICE=google
GOOGLE_TRANSLATE_API_KEY=your-key
```

## Basic Usage

### Simple Translation
```php
use Fastnet\TranslationServices\Facades\Translator;

$result = Translator::translate('Hello World', 'ar');
echo $result['translated_text'];
```

### Model Translation
```php
$product = Product::find(1);

Translator::translateModel(
    $product,
    ['name', 'description'],
    'ar'
);

echo $product->getTranslation('name', 'ar');
```

### Service Selection
```php
// Use specific service
$result = Translator::useService('deepl')
    ->translate('Hello', 'de');
```

## Configuration

### Environment Variables

```env
# Default service
TRANSLATION_SERVICE=google

# Google Translate
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=xxx

# DeepL
DEEPL_ENABLED=true
DEEPL_API_KEY=xxx
DEEPL_PRO=false

# Azure
AZURE_TRANSLATE_ENABLED=true
AZURE_TRANSLATE_API_KEY=xxx
AZURE_TRANSLATE_REGION=global

# OpenAI
OPENAI_TRANSLATE_ENABLED=true
OPENAI_API_KEY=xxx
OPENAI_MODEL=gpt-3.5-turbo

# Yandex
YANDEX_TRANSLATE_ENABLED=true
YANDEX_TRANSLATE_API_KEY=xxx
YANDEX_FOLDER_ID=xxx

# Amazon
AMAZON_TRANSLATE_ENABLED=true
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=us-east-1

# Ollama
OLLAMA_TRANSLATE_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2:7b
OLLAMA_TIMEOUT=120
```

## Real-World Examples

### E-commerce Product Translation
```php
class ProductTranslationService
{
    public function translateProduct(Product $product, array $languages)
    {
        foreach ($languages as $language) {
            Translator::useService('deepl')
                ->translateModel(
                    $product,
                    ['name', 'description', 'features'],
                    $language
                );
        }
    }
}
```

### Bulk Translation Command
```bash
php artisan products:translate ar --all
```

### API Endpoint
```php
public function translate(Request $request)
{
    $result = Translator::translate(
        $request->text,
        $request->target_language
    );

    return response()->json($result);
}
```

## Architecture

### Interface-Based Design
```
TranslationServiceInterface
    ↓
BaseTranslationService (abstract)
    ↓
├── GoogleTranslateService
├── DeepLService
├── AzureTranslateService
├── OpenAIService
├── YandexTranslateService
└── AmazonTranslateService
```

### Manager Pattern
`TranslationManager` orchestrates:
- Service selection
- Configuration management
- Instance caching
- Error handling
- Model translation logic

### Facade Pattern
`Translator` facade provides clean API:
```php
Translator::translate()
Translator::translateBatch()
Translator::translateModel()
Translator::useService()
```

## Performance Considerations

### 1. Service Selection
- **Google**: Best for high volume, low cost
- **DeepL**: Best quality for European languages
- **OpenAI**: Best for context-aware translations
- **Azure**: Best for enterprise/technical
- **Ollama**: Best for private local translation without external API calls

### 2. Batch Processing
Use `translateBatch()` for multiple texts to reduce API calls

### 3. Caching
Enable translation caching in config:
```php
'cache' => [
    'enabled' => true,
    'ttl' => 86400, // 24 hours
],
```

### 4. Queue Jobs
Queue long translations:
```php
TranslateProductJob::dispatch($product, 'ar');
```

## Security

1. **API Keys**: Store in `.env`, never commit
2. **Rate Limiting**: Implement on API endpoints
3. **Input Validation**: Validate text length, language codes
4. **Error Handling**: Don't expose internal errors to users

## Testing

### Manual Testing
```bash
php artisan tinker
```

```php
$result = Translator::translate('Hello', 'ar');
dd($result);
```

### Unit Testing
```php
public function test_translation()
{
    $result = Translator::translate('Hello', 'ar');

    $this->assertTrue($result['success']);
    $this->assertNotEmpty($result['translated_text']);
}
```

## Monitoring & Logging

### Check Logs
```bash
tail -f storage/logs/laravel.log | grep Translation
```

### Available Services
```php
$services = Translator::getAvailableServices();
foreach ($services as $service) {
    echo "{$service['display_name']}: " .
         ($service['configured'] ? '✓' : '✗') . "\n";
}
```

## Cost Optimization

1. **Use Free Tiers**: DeepL offers 500k chars/month free
2. **Cache Translations**: Avoid duplicate API calls
3. **Batch Operations**: Reduce API requests
4. **Choose Wisely**: Google cheaper for volume, DeepL better quality
5. **Monitor Usage**: Track API calls and costs

## Common Language Codes

| Code | Language | Code | Language |
|------|----------|------|----------|
| en | English | ar | Arabic |
| es | Spanish | fr | French |
| de | German | it | Italian |
| pt | Portuguese | ru | Russian |
| zh | Chinese | ja | Japanese |
| ko | Korean | hi | Hindi |
| ur | Urdu | nl | Dutch |
| pl | Polish | tr | Turkish |

## Troubleshooting

### Problem: Service not configured
**Solution**: Check `.env` file for API key

### Problem: Translation returns empty
**Solution**: Check logs for API errors, verify API key

### Problem: Model not translating
**Solution**: Ensure model uses `HasTranslations` trait

### Problem: API rate limit
**Solution**: Implement caching and rate limiting

## Support & Contribution

### Getting Help
- Check documentation
- Review examples
- Check logs
- Contact development team

### Best Practices
1. Always check `$result['success']` before using translated text
2. Log translation errors for debugging
3. Use appropriate service for content type
4. Implement fallbacks for failed translations
5. Cache frequently translated content
6. Queue bulk translations

## Future Enhancements

Potential additions:
- Translation memory/TM database
- Glossary/terminology management
- Translation quality scoring
- A/B testing for service comparison
- Auto-fallback to alternative service
- Translation cost tracking
- Bulk translation dashboard

## Version History

**v1.1.0** - Ollama local AI translation support
- Added Ollama service driver
- Added Ollama configuration and environment variables
- Updated docs and examples for local model translation

**v1.0.0** - Initial release
- 6 translation services
- Spatie Translatable integration
- Batch translation
- Model translation
- Comprehensive documentation

## License

MIT License - Free to use and modify

## Credits

Developed by Fastnet Development Team
Built with Laravel and love ❤️
