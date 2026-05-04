# Translation Services - Usage Examples

## Table of Contents
1. [Basic Usage](#basic-usage)
2. [Model Translation](#model-translation)
3. [Batch Translation](#batch-translation)
4. [Service Selection](#service-selection)
5. [Real-World Scenarios](#real-world-scenarios)
6. [Local AI Translation with Ollama](#local-ai-translation-with-ollama)

## Basic Usage

### Simple Translation

```php
use Fastnet\TranslationServices\Facades\Translator;

// Translate from English to Arabic
$result = Translator::translate('Hello World', 'ar', 'en');

echo $result['translated_text']; // مرحبا بالعالم
```

### Auto-Detect Source Language

```php
// Let the service detect the source language
$result = Translator::translate('Bonjour le monde', 'en');

echo $result['source_language']; // fr
echo $result['translated_text']; // Hello World
```

## Model Translation

### Setup Your Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'description', 'features'];

    public array $translatable = ['name', 'description', 'features'];
}
```

### Translate Single Model

```php
use App\Models\Product;
use Fastnet\TranslationServices\Facades\Translator;

$product = Product::find(1);

// Translate name and description to Arabic
$results = Translator::translateModel(
    $product,
    ['name', 'description'],
    'ar',
    'en',
    true // auto-save
);

// Check results
foreach ($results as $field => $result) {
    if ($result['success']) {
        echo "{$field} translated: {$result['translated_text']}\n";
    } else {
        echo "{$field} failed: {$result['error']['message']}\n";
    }
}

// Access translated content
echo $product->getTranslation('name', 'ar');
echo $product->getTranslation('description', 'ar');
```

### Translate to Multiple Languages

```php
$product = Product::find(1);
$languages = ['ar', 'es', 'fr', 'de', 'ur'];

foreach ($languages as $lang) {
    $results = Translator::translateModel(
        $product,
        ['name', 'description', 'features'],
        $lang,
        'en'
    );

    echo "Translated to {$lang}\n";
}

// Now the product has translations in all languages
$product->setLocale('ar')->name; // Arabic name
$product->setLocale('es')->description; // Spanish description
```

### Translate All Products

```php
$products = Product::all();

foreach ($products as $product) {
    Translator::translateModel(
        $product,
        ['name', 'description'],
        'ar',
        'en'
    );

    echo "Product #{$product->id} translated\n";
}
```

## Batch Translation

### Translate Multiple Texts

```php
$texts = [
    'Welcome to our store',
    'Browse our products',
    'Add to cart',
    'Checkout',
    'Thank you for your purchase'
];

$results = Translator::translateBatch($texts, 'ar', 'en');

foreach ($results as $index => $result) {
    echo "{$texts[$index]} => {$result['translated_text']}\n";
}
```

### Batch Translate Model Fields

```php
$product = Product::find(1);

// Get all translatable content
$texts = [
    $product->name,
    $product->description,
    $product->features
];

// Translate all at once
$results = Translator::translateBatch($texts, 'ar', 'en');

// Update model
if ($results[0]['success']) {
    $product->setTranslation('name', 'ar', $results[0]['translated_text']);
}
if ($results[1]['success']) {
    $product->setTranslation('description', 'ar', $results[1]['translated_text']);
}
if ($results[2]['success']) {
    $product->setTranslation('features', 'ar', $results[2]['translated_text']);
}

$product->save();
```

## Service Selection

### Using Different Services

```php
// Google Translate (default)
$result = Translator::translate('Hello', 'ar');

// DeepL for high quality
$result = Translator::useService('deepl')
    ->translate('Hello', 'de');

// OpenAI for context-aware translation
$result = Translator::useService('openai')
    ->translate('Welcome to our premium service', 'es');

// Azure for enterprise
$result = Translator::useService('azure')
    ->translate('Technical documentation', 'fr');

// Ollama for private local AI translation
$result = Translator::useService('ollama')
    ->translate('Internal policy document', 'ur');
```

### Service-Specific Translation

```php
// Use Google for general content
$generalContent = Translator::useService('google')
    ->translate($product->description, 'ar');

// Use DeepL for marketing content (higher quality)
$marketingContent = Translator::useService('deepl')
    ->translate($product->marketing_text, 'de');

// Use OpenAI for creative content
$creativeContent = Translator::useService('openai')
    ->translate($product->story, 'es');
```

## Real-World Scenarios

### Scenario 1: E-commerce Product Translation

```php
namespace App\Services;

use App\Models\Product;
use Fastnet\TranslationServices\Facades\Translator;
use Illuminate\Support\Facades\Log;

class ProductTranslationService
{
    public function translateProduct(Product $product, array $targetLanguages)
    {
        $results = [];

        foreach ($targetLanguages as $language) {
            try {
                $result = Translator::useService('deepl') // High quality for products
                    ->translateModel(
                        $product,
                        ['name', 'description', 'features', 'specifications'],
                        $language,
                        'en',
                        true
                    );

                $results[$language] = [
                    'success' => true,
                    'details' => $result
                ];

                Log::info("Product #{$product->id} translated to {$language}");
            } catch (\Exception $e) {
                $results[$language] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                Log::error("Failed to translate product #{$product->id} to {$language}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    public function translateCatalog(array $targetLanguages)
    {
        $products = Product::whereNull('deleted_at')->get();
        $summary = [];

        foreach ($products as $product) {
            $productResults = $this->translateProduct($product, $targetLanguages);
            $summary[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'results' => $productResults
            ];
        }

        return $summary;
    }
}
```

Usage:
```php
$service = new ProductTranslationService();
$results = $service->translateProduct($product, ['ar', 'es', 'fr']);
```

### Scenario 2: Blog Post Translation

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class BlogPost extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'content', 'excerpt', 'meta_description'];
}

// Service
class BlogTranslationService
{
    public function translatePost(BlogPost $post, string $targetLanguage)
    {
        // Use OpenAI for better context understanding in blog content
        return Translator::useService('openai')
            ->translateModel(
                $post,
                ['title', 'content', 'excerpt', 'meta_description'],
                $targetLanguage,
                'en'
            );
    }

    public function autoTranslateOnPublish(BlogPost $post, array $languages = ['ar', 'es', 'fr'])
    {
        foreach ($languages as $language) {
            $this->translatePost($post, $language);
        }

        $post->translated_languages = $languages;
        $post->save();
    }
}
```

### Scenario 3: Customer Support Tickets

```php
namespace App\Services;

use App\Models\SupportTicket;
use Fastnet\TranslationServices\Facades\Translator;

class TicketTranslationService
{
    public function translateTicketForSupport(SupportTicket $ticket, string $supportLanguage = 'en')
    {
        // Detect customer's language and translate to support language
        $result = Translator::translate(
            $ticket->message,
            $supportLanguage
        );

        if ($result['success']) {
            $ticket->translated_message = $result['translated_text'];
            $ticket->detected_language = $result['source_language'];
            $ticket->save();
        }

        return $result;
    }

    public function translateResponse(string $response, string $customerLanguage)
    {
        // Translate support response back to customer's language
        return Translator::translate($response, $customerLanguage, 'en');
    }
}
```

### Scenario 4: Multi-language Invoice

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Invoice extends Model
{
    use HasTranslations;

    public array $translatable = ['notes', 'terms_conditions'];
}

// Controller
class InvoiceController
{
    public function generateInvoice(Request $request)
    {
        $invoice = Invoice::create($request->validated());

        // Translate notes and terms to customer's language
        $customerLanguage = $request->user()->language ?? 'en';

        if ($customerLanguage !== 'en') {
            Translator::translateModel(
                $invoice,
                ['notes', 'terms_conditions'],
                $customerLanguage,
                'en'
            );
        }

        return response()->json($invoice);
    }
}
```

### Scenario 5: Artisan Command for Bulk Translation

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Fastnet\TranslationServices\Facades\Translator;

class TranslateProductsCommand extends Command
{
    protected $signature = 'products:translate
                            {language : Target language code}
                            {--service=google : Translation service to use}
                            {--fields=name,description : Fields to translate}';

    protected $description = 'Translate products to specified language';

    public function handle()
    {
        $language = $this->argument('language');
        $service = $this->option('service');
        $fields = explode(',', $this->option('fields'));

        $products = Product::all();
        $this->info("Translating {$products->count()} products to {$language} using {$service}...");

        $success = 0;
        $failed = 0;

        $this->withProgressBar($products, function ($product) use ($language, $service, $fields, &$success, &$failed) {
            try {
                $results = Translator::useService($service)
                    ->translateModel($product, $fields, $language, 'en');

                $hasSuccess = !empty(array_filter($results, fn($r) => $r['success']));
                if ($hasSuccess) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nProduct #{$product->id} failed: {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info("Translation completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Total', $products->count()]
            ]
        );
    }
}
```

Usage:
```bash
php artisan products:translate ar --service=deepl --fields=name,description,features
```

### Scenario 6: API Translation Endpoint

```php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Fastnet\TranslationServices\Facades\Translator;

class TranslationController extends Controller
{
    public function translate(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2',
            'service' => 'nullable|string|in:google,deepl,azure,openai,yandex,amazon,ollama'
        ]);

        $translator = Translator::class;

        if ($validated['service'] ?? null) {
            $translator = Translator::useService($validated['service']);
        }

        $result = $translator->translate(
            $validated['text'],
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        return response()->json($result);
    }

    public function translateBatch(Request $request)
    {
        $validated = $request->validate([
            'texts' => 'required|array|max:100',
            'texts.*' => 'required|string|max:5000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2',
            'service' => 'nullable|string|in:google,deepl,azure,openai,yandex,amazon,ollama'
        ]);

        $translator = Translator::class;

        if ($validated['service'] ?? null) {
            $translator = Translator::useService($validated['service']);
        }

        $results = $translator->translateBatch(
            $validated['texts'],
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        return response()->json($results);
    }

    public function getAvailableServices()
    {
        return response()->json(Translator::getAvailableServices());
    }
}
```

### Scenario 7: Event-Driven Translation

```php
namespace App\Listeners;

use App\Events\ProductCreated;
use Fastnet\TranslationServices\Facades\Translator;
use Illuminate\Contracts\Queue\ShouldQueue;

class TranslateNewProduct implements ShouldQueue
{
    public function handle(ProductCreated $event)
    {
        $product = $event->product;
        $targetLanguages = config('app.supported_languages', ['ar', 'es']);

        foreach ($targetLanguages as $language) {
            Translator::translateModel(
                $product,
                ['name', 'description'],
                $language,
                'en'
            );
        }
    }
}
```

## Best Practices

### 1. Use Appropriate Service for Content Type

```php
// Marketing content → DeepL (high quality)
$marketing = Translator::useService('deepl')->translate($text, 'de');

// Technical content → Azure (accuracy)
$technical = Translator::useService('azure')->translate($text, 'fr');

// Creative content → OpenAI (context-aware)
$creative = Translator::useService('openai')->translate($text, 'es');

// General content → Google (fast, reliable)
$general = Translator::useService('google')->translate($text, 'ar');

// Private/internal content -> Ollama (local model)
$private = Translator::useService('ollama')->translate($text, 'ur');
```

## Local AI Translation with Ollama

Ollama is useful when translations should run locally without sending content to a third-party API. Configure the service and use it like any other driver:

```env
TRANSLATION_SERVICE=ollama
OLLAMA_TRANSLATE_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2:7b
OLLAMA_TIMEOUT=120
```

```php
use Fastnet\TranslationServices\Facades\Translator;

$result = Translator::useService('ollama')
    ->translate('Welcome to the admin dashboard', 'ar', 'en');

if ($result['success']) {
    echo $result['translated_text'];
}
```

For batch translation, the Ollama driver processes each text through the configured local model:

```php
$results = Translator::useService('ollama')
    ->translateBatch([
        'Create invoice',
        'Send reminder',
        'Payment received',
    ], 'ur', 'en');
```

### 2. Error Handling

```php
$result = Translator::translate($text, 'ar');

if (!$result['success']) {
    // Log error
    Log::error('Translation failed', [
        'text' => $text,
        'service' => $result['service'],
        'error' => $result['error']
    ]);

    // Use fallback
    $fallbackText = $originalText;
} else {
    $translatedText = $result['translated_text'];
}
```

### 3. Queue Long Translations

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Fastnet\TranslationServices\Facades\Translator;

class TranslateProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public string $targetLanguage
    ) {}

    public function handle()
    {
        Translator::translateModel(
            $this->product,
            ['name', 'description', 'features'],
            $this->targetLanguage,
            'en'
        );
    }
}

// Dispatch
TranslateProductJob::dispatch($product, 'ar');
```
