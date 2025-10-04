<?php

namespace Fastnet\TranslationServices\Services;

use Fastnet\TranslationServices\Contracts\TranslationServiceInterface;

class AmazonTranslateService extends BaseTranslationService implements TranslationServiceInterface
{
    private const SERVICE = 'translate';

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (!$this->isConfigured()) {
            return $this->errorResponse('Amazon Translate credentials not configured');
        }

        try {
            $region = $this->config['region'] ?? 'us-east-1';
            $endpoint = "https://translate.{$region}.amazonaws.com/";

            $payload = json_encode([
                'Text' => $text,
                'SourceLanguageCode' => $sourceLanguage ?? 'auto',
                'TargetLanguageCode' => $this->normalizeLanguageCode($targetLanguage),
            ]);

            $headers = $this->generateAwsHeaders($payload, $region);

            $response = $this->makeRequest('POST', $endpoint, [
                'headers' => $headers,
                'body' => $payload,
            ]);

            if (!isset($response['TranslatedText'])) {
                throw new \Exception('Invalid response from Amazon Translate API');
            }

            return $this->successResponse(
                $response['TranslatedText'],
                $response['SourceLanguageCode'] ?? $sourceLanguage ?? 'unknown',
                $response['TargetLanguageCode'] ?? $targetLanguage,
                [
                    'applied_terminologies' => $response['AppliedTerminologies'] ?? [],
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Amazon Translate failed: ' . $e->getMessage(), $e);
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
        return !empty($this->config['access_key_id']) && !empty($this->config['secret_access_key']);
    }

    public function getServiceName(): string
    {
        return 'amazon';
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'ar', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko', 'hi', 'ur'];
    }

    /**
     * Generate AWS Signature Version 4 headers
     *
     * @param string $payload
     * @param string $region
     * @return array
     */
    private function generateAwsHeaders(string $payload, string $region): array
    {
        $accessKey = $this->config['access_key_id'];
        $secretKey = $this->config['secret_access_key'];
        $service = self::SERVICE;

        $datetime = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        $headers = [
            'Content-Type' => 'application/x-amz-json-1.1',
            'X-Amz-Target' => 'AWSShineFrontendService_20170701.TranslateText',
            'X-Amz-Date' => $datetime,
        ];

        $canonicalHeaders = '';
        foreach ($headers as $key => $value) {
            $canonicalHeaders .= strtolower($key) . ':' . trim($value) . "\n";
        }

        $signedHeaders = implode(';', array_map('strtolower', array_keys($headers)));
        $payloadHash = hash('sha256', $payload);

        $canonicalRequest = "POST\n/\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $credentialScope = "{$date}/{$region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$datetime}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $signingKey = $this->getSignatureKey($secretKey, $date, $region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        return $headers;
    }

    /**
     * Get AWS signing key
     *
     * @param string $key
     * @param string $date
     * @param string $region
     * @param string $service
     * @return string
     */
    private function getSignatureKey(string $key, string $date, string $region, string $service): string
    {
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $key, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
