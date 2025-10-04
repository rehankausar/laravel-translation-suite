<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class GoogleTranslateService extends BaseTranslationService implements TranslationServiceInterface
{
    private const API_URL = 'https://translation.googleapis.com/language/translate/v2';

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Google Translate API key not configured');
        }

        try {
            $params = [
                'q' => $text,
                'target' => $this->normalizeLanguageCode($targetLanguage),
                'key' => $this->config['api_key'],
                'format' => 'text',
            ];

            if ($sourceLanguage) {
                $params['source'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $response = $this->makeRequest('POST', self::API_URL, [
                'form_params' => $params,
            ]);

            if (!isset($response['data']['translations'][0])) {
                throw new \Exception('Invalid response from Google Translate API');
            }

            $translation = $response['data']['translations'][0];
            $detectedSourceLanguage = $translation['detectedSourceLanguage'] ?? $sourceLanguage ?? 'unknown';

            return $this->successResponse(
                $translation['translatedText'],
                $detectedSourceLanguage,
                $targetLanguage,
                [
                    'model' => $translation['model'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Google translation failed: ' . $e->getMessage(), $e);
        }
    }

    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return [
                $this->errorResponse('Google Translate API key not configured')
            ];
        }

        try {
            $params = [
                'q' => $texts,
                'target' => $this->normalizeLanguageCode($targetLanguage),
                'key' => $this->config['api_key'],
                'format' => 'text',
            ];

            if ($sourceLanguage) {
                $params['source'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $response = $this->makeRequest('POST', self::API_URL, [
                'form_params' => $params,
            ]);

            if (!isset($response['data']['translations'])) {
                throw new \Exception('Invalid response from Google Translate API');
            }

            $results = [];
            foreach ($response['data']['translations'] as $translation) {
                $detectedSourceLanguage = $translation['detectedSourceLanguage'] ?? $sourceLanguage ?? 'unknown';
                $results[] = $this->successResponse(
                    $translation['translatedText'],
                    $detectedSourceLanguage,
                    $targetLanguage,
                    [
                        'model' => $translation['model'] ?? null,
                    ]
                );
            }

            return $results;
        } catch (\Exception $e) {
            return [$this->errorResponse('Google batch translation failed: ' . $e->getMessage(), $e)];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }

    public function getServiceName(): string
    {
        return 'google';
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko', 'hi', 'ur'];
    }
}
