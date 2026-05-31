<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class DeepLService extends BaseTranslationService implements TranslationServiceInterface
{
    private const API_URL_FREE = 'https://api-free.deepl.com/v2/translate';
    private const API_URL_PRO = 'https://api.deepl.com/v2/translate';

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('DeepL API key not configured');
        }

        try {
            $params = [
                'text' => [$text],
                'target_lang' => $this->normalizeLanguageCode($targetLanguage),
            ];

            if ($sourceLanguage) {
                $params['source_lang'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $apiUrl = $this->config['pro'] ?? false ? self::API_URL_PRO : self::API_URL_FREE;

            $response = $this->makeRequest('POST', $apiUrl, [
                'headers' => ['Authorization' => 'DeepL-Auth-Key ' . $this->config['api_key']],
                'json' => $params,
            ]);

            if (!isset($response['translations'][0])) {
                throw new \Exception('Invalid response from DeepL API');
            }

            $translation = $response['translations'][0];

            return $this->successResponse(
                $translation['text'],
                $translation['detected_source_language'] ?? $sourceLanguage ?? 'unknown',
                $targetLanguage,
                [
                    'billed_characters' => $response['billed_characters'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('DeepL translation failed: ' . $e->getMessage(), $e);
        }
    }

    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return [
                $this->errorResponse('DeepL API key not configured')
            ];
        }

        try {
            $params = [
                'text' => $texts,
                'target_lang' => $this->normalizeLanguageCode($targetLanguage),
            ];

            if ($sourceLanguage) {
                $params['source_lang'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $apiUrl = $this->config['pro'] ?? false ? self::API_URL_PRO : self::API_URL_FREE;

            $response = $this->makeRequest('POST', $apiUrl, [
                'headers' => ['Authorization' => 'DeepL-Auth-Key ' . $this->config['api_key']],
                'json' => $params,
            ]);

            if (!isset($response['translations'])) {
                throw new \Exception('Invalid response from DeepL API');
            }

            $results = [];
            foreach ($response['translations'] as $translation) {
                $results[] = $this->successResponse(
                    $translation['text'],
                    $translation['detected_source_language'] ?? $sourceLanguage ?? 'unknown',
                    $targetLanguage
                );
            }

            return $results;
        } catch (\Exception $e) {
            return [$this->errorResponse('DeepL batch translation failed: ' . $e->getMessage(), $e)];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }

    public function getServiceName(): string
    {
        return 'deepl';
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'de', 'fr', 'es', 'pt', 'it', 'nl', 'pl', 'ru', 'ja', 'zh', 'ar'];
    }

    protected function normalizeLanguageCode(string $languageCode): string
    {
        // DeepL uses uppercase language codes
        return strtoupper($languageCode);
    }
}
