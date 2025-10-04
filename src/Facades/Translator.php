<?php

namespace Fastnet\TranslationServices\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fastnet\TranslationServices\Contracts\TranslationServiceInterface getService()
 * @method static \Fastnet\TranslationServices\Contracts\TranslationServiceInterface driver(string $name)
 * @method static \Fastnet\TranslationServices\TranslationManager useService(string $name)
 * @method static array translate(string $text, string $targetLanguage, ?string $sourceLanguage = null)
 * @method static array translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null)
 * @method static array translateModel($model, array $fields, string $targetLanguage, ?string $sourceLanguage = null, bool $save = true)
 * @method static array getAvailableServices()
 * @method static bool hasService(string $name)
 *
 * @see \Fastnet\TranslationServices\TranslationManager
 */
class Translator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'translator_service';
    }
}
