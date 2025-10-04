<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class YandexTranslateService extends BaseTranslationService implements TranslationServiceInterface
{
    private const API_URL = 'https://translate.api.cloud.yandex.net/translate/v2/translate';

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Yandex Translate API key not configured');
        }

        try {
            $body = [
                'texts' => [$text],
                'targetLanguageCode' => $this->normalizeLanguageCode($targetLanguage),
                'folderId' => $this->config['folder_id'],
            ];

            if ($sourceLanguage) {
                $body['sourceLanguageCode'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $response = $this->makeRequest('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Api-Key ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            if (!isset($response['translations'][0]['text'])) {
                throw new \Exception('Invalid response from Yandex Translate API');
            }

            $translation = $response['translations'][0];

            return $this->successResponse(
                $translation['text'],
                $translation['detectedLanguageCode'] ?? $sourceLanguage ?? 'unknown',
                $targetLanguage
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Yandex translation failed: ' . $e->getMessage(), $e);
        }
    }

    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return [
                $this->errorResponse('Yandex Translate API key not configured')
            ];
        }

        try {
            $body = [
                'texts' => $texts,
                'targetLanguageCode' => $this->normalizeLanguageCode($targetLanguage),
                'folderId' => $this->config['folder_id'],
            ];

            if ($sourceLanguage) {
                $body['sourceLanguageCode'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $response = $this->makeRequest('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Api-Key ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            if (!isset($response['translations'])) {
                throw new \Exception('Invalid response from Yandex Translate API');
            }

            $results = [];
            foreach ($response['translations'] as $translation) {
                $results[] = $this->successResponse(
                    $translation['text'],
                    $translation['detectedLanguageCode'] ?? $sourceLanguage ?? 'unknown',
                    $targetLanguage
                );
            }

            return $results;
        } catch (\Exception $e) {
            return [$this->errorResponse('Yandex batch translation failed: ' . $e->getMessage(), $e)];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['folder_id']);
    }

    public function getServiceName(): string
    {
        return 'yandex';
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko', 'tr', 'uk'];
    }
}
