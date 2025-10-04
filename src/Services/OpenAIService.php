<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class OpenAIService extends BaseTranslationService implements TranslationServiceInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('OpenAI API key not configured');
        }

        try {
            $sourceInfo = $sourceLanguage ? "from {$sourceLanguage}" : '';
            //$prompt = "Translate the following text {$sourceInfo} to {$targetLanguage}. Return only the translated text without any explanations:\n\n{$text}";
            $prompt = <<<EOT
        Translate the following text {$sourceInfo} to {$targetLanguage}.
        - Preserve the meaning, context, and any important details.
        - If the original text contains formatting (such as newlines, bullet points, or sections), maintain the formatting in the translation.
        - Do NOT include any explanations, footnotes, or extra text—**only the translated content**.

        Text to translate:
        {$text}
        EOT;

            $response = $this->makeRequest('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->config['model'] ?? 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a expert, native-level translator. Translate text accurately while preserving meaning and context.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => $this->config['max_tokens'] ?? 1000,
                ],
            ]);

            if (!isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response from OpenAI API');
            }

            $translatedText = trim($response['choices'][0]['message']['content']);

            return $this->successResponse(
                $translatedText,
                $sourceLanguage ?? 'auto-detected',
                $targetLanguage,
                [
                    'model' => $response['model'] ?? null,
                    'tokens_used' => $response['usage']['total_tokens'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('OpenAI translation failed: ' . $e->getMessage(), $e);
        }
    }

    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $results = [];
        foreach ($texts as $text) {
            $results[] = $this->translate($text, $targetLanguage, $sourceLanguage);
        }
        return $results;
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }

    public function getServiceName(): string
    {
        return 'openai';
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko', 'hi', 'ur', 'nl', 'pl', 'tr', 'th', 'vi'];
    }
}
