<?php

namespace Fastnet\TranslationServices;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;
use Fastnet\TranslationServices\Services\GoogleTranslateService;
use Fastnet\TranslationServices\Services\AzureTranslateService;
use Fastnet\TranslationServices\Services\DeepLService;
use Fastnet\TranslationServices\Services\YandexTranslateService;
use Fastnet\TranslationServices\Services\AmazonTranslateService;
use Fastnet\TranslationServices\Services\OpenAIService;
use Fastnet\TranslationServices\Services\OllamaService;
use Illuminate\Support\Facades\Log;

class TranslationManager
{
    protected array $config;
    protected ?TranslationServiceInterface $activeService = null;
    protected array $serviceInstances = [];

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('translation-services', []);
    }

    /**
     * Get the active translation service
     *
     * @return TranslationServiceInterface
     * @throws \Exception
     */
    public function getService(): TranslationServiceInterface
    {
        if ($this->activeService) {
            return $this->activeService;
        }

        $defaultService = $this->config['default'] ?? 'google';
        return $this->activeService = $this->driver($defaultService);
    }

    /**
     * Get a specific translation service driver
     *
     * @param string $name
     * @return TranslationServiceInterface
     * @throws \Exception
     */
    public function driver(string $name): TranslationServiceInterface
    {
        if (isset($this->serviceInstances[$name])) {
            return $this->serviceInstances[$name];
        }

        $config = $this->config['services'][$name] ?? [];

        if (!$config['enabled'] ?? false) {
            throw new \Exception("Translation service '{$name}' is not enabled");
        }

        $service = match ($name) {
            'google' => new GoogleTranslateService($config),
            'azure' => new AzureTranslateService($config),
            'deepl' => new DeepLService($config),
            'yandex' => new YandexTranslateService($config),
            'amazon' => new AmazonTranslateService($config),
            'openai' => new OpenAIService($config),
            'ollama' => new OllamaService($config),
            default => throw new \Exception("Unknown translation service: {$name}"),
        };

        if (!$service->isConfigured()) {
            throw new \Exception("Translation service '{$name}' is not properly configured");
        }

        return $this->serviceInstances[$name] = $service;
    }

    /**
     * Set the active service
     *
     * @param string $name
     * @return $this
     */
    public function useService(string $name): self
    {
        $this->activeService = $this->driver($name);
        return $this;
    }

    /**
     * Translate text using the active service
     *
     * @param string $text
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        try {
            return $this->getService()->translate($text, $targetLanguage, $sourceLanguage);
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'error' => $e->getMessage(),
                'service' => $this->activeService?->getServiceName(),
            ]);

            return [
                'success' => false,
                'translated_text' => null,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'service' => $this->activeService?->getServiceName() ?? 'unknown',
                'metadata' => [],
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
            ];
        }
    }

    /**
     * Translate multiple texts in batch
     *
     * @param array $texts
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        try {
            return $this->getService()->translateBatch($texts, $targetLanguage, $sourceLanguage);
        } catch (\Exception $e) {
            Log::error('Batch translation failed', [
                'error' => $e->getMessage(),
                'service' => $this->activeService?->getServiceName(),
            ]);

            return [
                [
                    'success' => false,
                    'translated_text' => null,
                    'source_language' => $sourceLanguage,
                    'target_language' => $targetLanguage,
                    'service' => $this->activeService?->getServiceName() ?? 'unknown',
                    'metadata' => [],
                    'error' => [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                ]
            ];
        }
    }

    /**
     * Translate model translatable fields
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $fields Fields to translate
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @param bool $save Auto-save the model after translation
     * @return array Translation results
     */
    public function translateModel(
        $model,
        array $fields,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        bool $save = true
    ): array {
        $results = [];

        foreach ($fields as $field) {
            if (!method_exists($model, 'getTranslations')) {
                $results[$field] = [
                    'success' => false,
                    'error' => ['message' => 'Model does not use HasTranslations trait'],
                ];
                continue;
            }

            $sourceText = $model->getTranslation($field, $sourceLanguage ?? app()->getLocale());

            if (empty($sourceText)) {
                $results[$field] = [
                    'success' => false,
                    'error' => ['message' => 'Source text is empty'],
                ];
                continue;
            }

            $result = $this->translate($sourceText, $targetLanguage, $sourceLanguage);

            if ($result['success']) {
                $model->setTranslation($field, $targetLanguage, $result['translated_text']);
            }

            $results[$field] = $result;
        }

        if ($save && !empty(array_filter($results, fn($r) => $r['success']))) {
            $model->save();
        }

        return $results;
    }

    /**
     * Get list of available services
     *
     * @return array
     */
    public function getAvailableServices(): array
    {
        $services = [];

        foreach ($this->config['services'] ?? [] as $name => $config) {
            if ($config['enabled'] ?? false) {
                try {
                    $service = $this->driver($name);
                    $services[] = [
                        'name' => $name,
                        'display_name' => $config['display_name'] ?? ucfirst($name),
                        'configured' => $service->isConfigured(),
                        'supported_languages' => $service->getSupportedLanguages(),
                    ];
                } catch (\Exception $e) {
                    // Service not available
                }
            }
        }

        return $services;
    }

    /**
     * Check if a service is available
     *
     * @param string $name
     * @return bool
     */
    public function hasService(string $name): bool
    {
        try {
            $service = $this->driver($name);
            return $service->isConfigured();
        } catch (\Exception $e) {
            return false;
        }
    }
}
