# Integration Guide - Translation Services Package

This guide shows how to integrate the Translation Services package into your existing Laravel application.

## Step 1: Installation

### Add to composer.json

In your main project's `composer.json`, add:

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

### Install

```bash
composer update fastnet/translation-services
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=translation-services-config
```

## Step 2: Environment Configuration

Add to your `.env`:

```env
# Translation Service
TRANSLATION_SERVICE=google

# Google Translate (Recommended for general use)
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=your-google-api-key-here

# DeepL (Recommended for high-quality translations)
DEEPL_ENABLED=true
DEEPL_API_KEY=your-deepl-api-key-here
DEEPL_PRO=false

# OpenAI (Recommended for context-aware translations)
OPENAI_TRANSLATE_ENABLED=true
OPENAI_API_KEY=your-openai-api-key-here
OPENAI_MODEL=gpt-3.5-turbo

# Ollama (Recommended for private local AI translations)
OLLAMA_TRANSLATE_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2:7b
OLLAMA_TIMEOUT=120
```

## Step 3: Update Existing Models

### Optional: Use Ollama as the Default Service

Ollama uses a local model through Ollama's OpenAI-compatible chat API. Make sure Ollama is running and the configured model is available before setting it as the default:

```bash
ollama pull qwen2:7b
ollama serve
```

```env
TRANSLATION_SERVICE=ollama
OLLAMA_TRANSLATE_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2:7b
```

### Example: Product Model

Your existing model probably looks like this:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'description', 'price', 'category_id'];

    public array $translatable = ['name', 'description'];
}
```

No changes needed! The package works with your existing setup.

### Example: Item Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Item extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'description', 'sku', 'price'];

    public array $translatable = ['name', 'description'];
}
```

## Step 4: Create Translation Service

Create a service to handle translations in your app:

```php
// app/Services/TranslationService.php

namespace App\Services;

use Fastnet\TranslationServices\Facades\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Translate model to multiple languages
     */
    public function translateToMultipleLanguages(
        Model $model,
        array $fields,
        array $languages,
        string $sourceLanguage = 'en'
    ): array {
        $results = [];

        foreach ($languages as $language) {
            try {
                $result = Translator::translateModel(
                    $model,
                    $fields,
                    $language,
                    $sourceLanguage,
                    true
                );

                $results[$language] = [
                    'success' => true,
                    'details' => $result
                ];
            } catch (\Exception $e) {
                $results[$language] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                Log::error("Translation failed for {$language}", [
                    'model' => get_class($model),
                    'id' => $model->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Get supported languages from config
     */
    public function getSupportedLanguages(): array
    {
        return config('app.supported_languages', ['en', 'ar', 'es', 'fr']);
    }
}
```

## Step 5: Add Translation to Controllers

### Product Controller Example

```php
// app/Http/Controllers/Admin/Item/ItemController.php

namespace App\Http\Controllers\Admin\Item;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        private TranslationService $translationService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'auto_translate' => 'boolean',
            'target_languages' => 'array',
        ]);

        $item = Item::create($validated);

        // Auto-translate if requested
        if ($request->boolean('auto_translate')) {
            $languages = $request->input('target_languages', ['ar']);
            $this->translationService->translateToMultipleLanguages(
                $item,
                ['name', 'description'],
                $languages
            );

            alert()->success('Item created and translated successfully');
        } else {
            alert()->success('Item created successfully');
        }

        return redirect()->route('admin.items.show', $item);
    }

    public function translate(Request $request, Item $item)
    {
        $validated = $request->validate([
            'languages' => 'required|array',
            'languages.*' => 'required|string|size:2',
            'fields' => 'array',
            'fields.*' => 'required|string',
        ]);

        $fields = $validated['fields'] ?? ['name', 'description'];
        $results = $this->translationService->translateToMultipleLanguages(
            $item,
            $fields,
            $validated['languages']
        );

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }
}
```

### Add Translation Routes

```php
// routes/admin.php

Route::middleware(['auth:web'])->group(function () {
    // Translation routes
    Route::post('items/{item}/translate', [ItemController::class, 'translate'])
        ->name('items.translate');

    Route::post('products/{product}/translate', [ProductController::class, 'translate'])
        ->name('products.translate');

    Route::post('invoices/{invoice}/translate-notes', [InvoiceController::class, 'translateNotes'])
        ->name('invoices.translate-notes');
});
```

## Step 6: Add Translation UI

### Add Translation Button to Item Form

```blade
{{-- resources/views/admin/items/create.blade.php --}}

<form method="POST" action="{{ route('admin.items.store') }}">
    @csrf

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" required></textarea>
    </div>

    {{-- Auto Translation Options --}}
    <div class="card mt-3">
        <div class="card-header">
            <h5>Translation Options</h5>
        </div>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="auto_translate" id="auto_translate">
                <label class="form-check-label" for="auto_translate">
                    Auto-translate after creation
                </label>
            </div>

            <div id="translation_options" style="display: none;" class="mt-3">
                <label>Target Languages:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="target_languages[]" value="ar" checked>
                    <label class="form-check-label">Arabic</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="target_languages[]" value="es">
                    <label class="form-check-label">Spanish</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="target_languages[]" value="fr">
                    <label class="form-check-label">French</label>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mt-3">Create Item</button>
</form>

@push('script_page')
<script>
    $('#auto_translate').change(function() {
        $('#translation_options').toggle(this.checked);
    });
</script>
@endpush
```

### Add Translation Button to Edit Page

```blade
{{-- resources/views/admin/items/edit.blade.php --}}

<div class="card mt-3">
    <div class="card-header">
        <h5>Translations</h5>
    </div>
    <div class="card-body">
        <button type="button" class="btn btn-primary" onclick="openTranslationModal()">
            <i class="fas fa-language"></i> Translate Item
        </button>

        @if($item->getTranslations('name'))
            <div class="mt-3">
                <strong>Available Translations:</strong>
                @foreach($item->getTranslations('name') as $locale => $value)
                    <span class="badge bg-info">{{ strtoupper($locale) }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Translation Modal --}}
<div class="modal fade" id="translationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Translate Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Target Languages:</label>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" value="ar" id="lang_ar" checked>
                        <label class="form-check-label" for="lang_ar">Arabic</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" value="es" id="lang_es">
                        <label class="form-check-label" for="lang_es">Spanish</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" value="fr" id="lang_fr">
                        <label class="form-check-label" for="lang_fr">French</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" value="de" id="lang_de">
                        <label class="form-check-label" for="lang_de">German</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="translateItem()">
                    <i class="fas fa-language"></i> Translate
                </button>
            </div>
        </div>
    </div>
</div>

@push('script_page')
<script>
    function openTranslationModal() {
        $('#translationModal').modal('show');
    }

    function translateItem() {
        const languages = [];
        $('.form-check-input:checked').each(function() {
            languages.push($(this).val());
        });

        if (languages.length === 0) {
            Swal.fire('Error', 'Please select at least one language', 'error');
            return;
        }

        // Show loading
        Swal.fire({
            title: 'Translating...',
            text: 'Please wait while we translate your item',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Make API call
        $.ajax({
            url: '{{ route("admin.items.translate", $item) }}',
            method: 'POST',
            data: {
                languages: languages,
                fields: ['name', 'description'],
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Item translated successfully',
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                Swal.fire('Error', 'Translation failed', 'error');
            }
        });
    }
</script>
@endpush
```

## Step 7: Create Artisan Command

```php
// app/Console/Commands/TranslateItemsCommand.php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\TranslationService;
use Illuminate\Console\Command;

class TranslateItemsCommand extends Command
{
    protected $signature = 'items:translate
                            {language : Target language code}
                            {--all : Translate all items}
                            {--id=* : Specific item IDs to translate}';

    protected $description = 'Translate items to specified language';

    public function __construct(
        private TranslationService $translationService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $language = $this->argument('language');

        if ($this->option('all')) {
            $items = Item::all();
        } elseif ($ids = $this->option('id')) {
            $items = Item::whereIn('id', $ids)->get();
        } else {
            $this->error('Please specify --all or --id option');
            return 1;
        }

        $this->info("Translating {$items->count()} items to {$language}...");

        $success = 0;
        $failed = 0;

        $this->withProgressBar($items, function ($item) use ($language, &$success, &$failed) {
            $results = $this->translationService->translateToMultipleLanguages(
                $item,
                ['name', 'description'],
                [$language]
            );

            if ($results[$language]['success']) {
                $success++;
            } else {
                $failed++;
            }
        });

        $this->newLine(2);
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Total', $items->count()]
            ]
        );

        return 0;
    }
}
```

Register in `app/Console/Kernel.php`:

```php
protected $commands = [
    Commands\TranslateItemsCommand::class,
];
```

Usage:
```bash
# Translate all items
php artisan items:translate ar --all

# Translate specific items
php artisan items:translate ar --id=1 --id=2 --id=3
```

## Step 8: Testing

### Test in Tinker

```bash
php artisan tinker
```

```php
use App\Models\Item;
use Fastnet\TranslationServices\Facades\Translator;

// Get an item
$item = Item::first();

// Translate to Arabic
$result = Translator::translateModel($item, ['name', 'description'], 'ar', 'en');

// Check result
dd($result);

// Access translated content
$item->getTranslation('name', 'ar');
$item->getTranslation('description', 'ar');
```

## Step 9: Configuration

### Supported Languages

Add to `config/app.php`:

```php
'supported_languages' => ['en', 'ar', 'es', 'fr', 'de', 'ur'],
```

### Language Names

```php
'language_names' => [
    'en' => 'English',
    'ar' => 'العربية',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'ur' => 'اردو',
],
```

## Complete Example: Invoice Translation

```php
// app/Http/Controllers/Admin/Invoice/InvoiceController.php

public function translateNotes(Request $request, Invoice $invoice)
{
    $validated = $request->validate([
        'target_language' => 'required|string|size:2',
    ]);

    $result = Translator::translateModel(
        $invoice,
        ['notes', 'terms_conditions'],
        $validated['target_language'],
        'en',
        true
    );

    if ($result['notes']['success'] && $result['terms_conditions']['success']) {
        return response()->json([
            'success' => true,
            'message' => 'Invoice translated successfully',
            'translations' => [
                'notes' => $invoice->getTranslation('notes', $validated['target_language']),
                'terms' => $invoice->getTranslation('terms_conditions', $validated['target_language']),
            ]
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Translation failed',
        'errors' => $result
    ], 422);
}
```

## Troubleshooting

### Issue: "Service not configured"

**Solution**: Check your `.env` file and ensure API keys are set:

```env
GOOGLE_TRANSLATE_API_KEY=your-key-here
```

For Ollama, verify the local server and model:

```bash
ollama list
```

```env
TRANSLATION_SERVICE=ollama
OLLAMA_TRANSLATE_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=qwen2:7b
```

### Issue: "Translation fails silently"

**Solution**: Check logs:

```bash
tail -f storage/logs/laravel.log
```

### Issue: "Model doesn't have translations"

**Solution**: Ensure model uses `HasTranslations` trait and defines `$translatable` array:

```php
use Spatie\Translatable\HasTranslations;

class YourModel extends Model
{
    use HasTranslations;

    public array $translatable = ['field1', 'field2'];
}
```

## Next Steps

1. Add translation buttons to all relevant forms
2. Create bulk translation jobs for existing data
3. Set up automated translation on model creation
4. Add translation status indicators in admin panel
5. Create translation audit trail
6. Use Ollama for private/local translation workflows where external API calls are not desired

## Support

For issues or questions, contact the development team.
