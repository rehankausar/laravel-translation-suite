<?php

namespace Fastnet\TranslationServices\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

abstract class BaseTranslationService
{
    protected Client $client;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
        ]);
    }

    /**
     * Create standardized success response
     *
     * @param string $translatedText
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @param array $metadata
     * @return array
     */
    protected function successResponse(
        string $translatedText,
        string $sourceLanguage,
        string $targetLanguage,
        array $metadata = []
    ): array {
        return [
            'success' => true,
            'translated_text' => $translatedText,
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'service' => $this->getServiceName(),
            'metadata' => $metadata,
            'error' => null,
        ];
    }

    /**
     * Create standardized error response
     *
     * @param string $message
     * @param \Throwable|null $exception
     * @return array
     */
    protected function errorResponse(string $message, ?\Throwable $exception = null): array
    {
        $error = [
            'message' => $message,
            'code' => $exception ? $exception->getCode() : null,
        ];

        if ($exception && config('app.debug')) {
            $error['trace'] = $exception->getTraceAsString();
        }

        // Log the error
        Log::error("[{$this->getServiceName()}] Translation error: {$message}", [
            'exception' => $exception ? $exception->getMessage() : null,
        ]);

        return [
            'success' => false,
            'translated_text' => null,
            'source_language' => null,
            'target_language' => null,
            'service' => $this->getServiceName(),
            'metadata' => [],
            'error' => $error,
        ];
    }

    /**
     * Make HTTP request with error handling
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    protected function makeRequest(string $method, string $url, array $options = [])
    {
        try {
            $response = $this->client->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \Exception("HTTP request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get service name
     *
     * @return string
     */
    abstract public function getServiceName(): string;

    /**
     * Normalize language code for specific service
     *
     * @param string $languageCode
     * @return string
     */
    protected function normalizeLanguageCode(string $languageCode): string
    {
        // Override in child classes if needed
        return strtolower($languageCode);
    }
}
