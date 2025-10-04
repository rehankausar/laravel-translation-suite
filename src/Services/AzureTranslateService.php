<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class AzureTranslateService extends BaseTranslationService implements TranslationServiceInterface
{
    private const API_URL = 'https://api.cognitive.microsofttranslator.com/translate';

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Azure Translator API key not configured');
        }

        try {
            $params = [
                'api-version' => '3.0',
                'to' => $this->normalizeLanguageCode($targetLanguage),
            ];

            if ($sourceLanguage) {
                $params['from'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $headers = [
                'Ocp-Apim-Subscription-Key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ];

            if (!empty($this->config['region'])) {
                $headers['Ocp-Apim-Subscription-Region'] = $this->config['region'];
            }

            $response = $this->makeRequest('POST', self::API_URL, [
                'query' => $params,
                'headers' => $headers,
                'json' => [
                    ['text' => $text]
                ],
            ]);

            if (!isset($response[0]['translations'][0])) {
                throw new \Exception('Invalid response from Azure Translator API');
            }

            $translation = $response[0]['translations'][0];
            $detectedLanguage = $response[0]['detectedLanguage']['language'] ?? $sourceLanguage ?? 'unknown';

            return $this->successResponse(
                $translation['text'],
                $detectedLanguage,
                $targetLanguage,
                [
                    'confidence' => $response[0]['detectedLanguage']['score'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Azure translation failed: ' . $e->getMessage(), $e);
        }
    }

    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return [
                $this->errorResponse('Azure Translator API key not configured')
            ];
        }

        try {
            $params = [
                'api-version' => '3.0',
                'to' => $this->normalizeLanguageCode($targetLanguage),
            ];

            if ($sourceLanguage) {
                $params['from'] = $this->normalizeLanguageCode($sourceLanguage);
            }

            $headers = [
                'Ocp-Apim-Subscription-Key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ];

            if (!empty($this->config['region'])) {
                $headers['Ocp-Apim-Subscription-Region'] = $this->config['region'];
            }

            $body = array_map(fn($text) => ['text' => $text], $texts);

            $response = $this->makeRequest('POST', self::API_URL, [
                'query' => $params,
                'headers' => $headers,
                'json' => $body,
            ]);

            $results = [];
            foreach ($response as $item) {
                if (!isset($item['translations'][0])) {
                    $results[] = $this->errorResponse('Invalid translation in batch response');
                    continue;
                }

                $translation = $item['translations'][0];
                $detectedLanguage = $item['detectedLanguage']['language'] ?? $sourceLanguage ?? 'unknown';

                $results[] = $this->successResponse(
                    $translation['text'],
                    $detectedLanguage,
                    $targetLanguage,
                    [
                        'confidence' => $item['detectedLanguage']['score'] ?? null,
                    ]
                );
            }

            return $results;
        } catch (\Exception $e) {
            return [$this->errorResponse('Azure batch translation failed: ' . $e->getMessage(), $e)];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }

    public function getServiceName(): string
    {
        return 'azure';
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko', 'hi', 'ur', 'nl', 'pl'];
    }
}
