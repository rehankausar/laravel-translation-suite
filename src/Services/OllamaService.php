<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class OllamaService extends BaseTranslationService implements TranslationServiceInterface
{
    private function getApiUrl(): string
    {
        $host = rtrim($this->config['host'] ?? 'http://localhost:11434', '/');
        return $host . '/v1/chat/completions';
    }

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Ollama host or model not configured');
        }

        try {
            $sourceInfo = $sourceLanguage ? "from {$sourceLanguage}" : '';
            $prompt = <<<EOT
        Translate the following text {$sourceInfo} to {$targetLanguage}.
        - Preserve the meaning, context, and any important details.
        - If the original text contains formatting (such as newlines, bullet points, or sections), maintain the formatting in the translation.
        - Do NOT include any explanations, footnotes, or extra text - only the translated content.

        Text to translate:
        {$text}
        EOT;

            $response = $this->makeRequest('POST', $this->getApiUrl(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model'       => $this->config['model'] ?? 'qwen2:7b',
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are a expert, native-level translator. Translate text accurately while preserving meaning and context.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                ],
            ]);

            if (!isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response from Ollama API');
            }

            $translatedText = trim($response['choices'][0]['message']['content']);

            return $this->successResponse(
                $translatedText,
                $sourceLanguage ?? 'auto-detected',
                $targetLanguage,
                [
                    'model'       => $response['model'] ?? $this->config['model'] ?? null,
                    'tokens_used' => $response['usage']['total_tokens'] ?? null,
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Ollama translation failed: ' . $e->getMessage(), $e);
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
        return !empty($this->config['host']) && !empty($this->config['model']);
    }

    public function getServiceName(): string
    {
        return 'ollama';
    }

    public function getSupportedLanguages(): array
    {
        return [
            'en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko',
            'hi', 'ur', 'nl', 'pl', 'tr', 'th', 'vi', 'id', 'ms', 'fa', 'he',
            'bn', 'ta', 'te', 'mr', 'pa', 'gu', 'uk', 'cs', 'sk', 'ro', 'hu',
            'sv', 'da', 'no', 'fi', 'el', 'bg', 'hr', 'sr', 'lt', 'lv', 'et',
        ];
    }
}
