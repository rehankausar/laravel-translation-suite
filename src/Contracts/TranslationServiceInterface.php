<?php

namespace Fastnet\TranslationServices\Contracts;

interface TranslationServiceInterface
{
    /**
     * Translate text from source language to target language
     *
     * @param string $text Text to translate
     * @param string $targetLanguage Target language code (e.g., 'en', 'ar', 'es')
     * @param string|null $sourceLanguage Source language code (null for auto-detect)
     * @return array Standardized response with translation result
     */
    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array;

    /**
     * Translate multiple texts in batch
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @param string|null $sourceLanguage Source language code
     * @return array Array of standardized translation responses
     */
    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array;

    /**
     * Check if the service is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get the service name
     *
     * @return string
     */
    public function getServiceName(): string;

    /**
     * Get supported languages
     *
     * @return array
     */
    public function getSupportedLanguages(): array;
}
