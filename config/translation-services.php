<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Translation Service
    |--------------------------------------------------------------------------
    |
    | This option controls the default translation service that will be used
    | by the framework. You may set this to any of the services defined
    | in the "services" array below.
    |
    */

    'default' => env('TRANSLATION_SERVICE', 'google'),

    /*
    |--------------------------------------------------------------------------
    | Translation Services
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many translation services as you wish.
    | Each service has its own credentials and configuration options.
    |
    */

    'services' => [

        'google' => [
            'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
            'display_name' => 'Google Translate',
            'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
            'timeout' => 30,
        ],

        'deepl' => [
            'enabled' => env('DEEPL_ENABLED', false),
            'display_name' => 'DeepL',
            'api_key' => env('DEEPL_API_KEY'),
            'pro' => env('DEEPL_PRO', false), // false = free tier, true = pro tier
            'timeout' => 30,
        ],

        'azure' => [
            'enabled' => env('AZURE_TRANSLATE_ENABLED', false),
            'display_name' => 'Azure Translator',
            'api_key' => env('AZURE_TRANSLATE_API_KEY'),
            'region' => env('AZURE_TRANSLATE_REGION', 'global'), // e.g., 'eastus', 'westeurope'
            'timeout' => 30,
        ],

        'openai' => [
            'enabled' => env('OPENAI_TRANSLATE_ENABLED', false),
            'display_name' => 'OpenAI (ChatGPT)',
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'), // or 'gpt-4'
            'max_tokens' => 1000,
            'timeout' => 60,
        ],

        'yandex' => [
            'enabled' => env('YANDEX_TRANSLATE_ENABLED', false),
            'display_name' => 'Yandex Translate',
            'api_key' => env('YANDEX_TRANSLATE_API_KEY'),
            'folder_id' => env('YANDEX_FOLDER_ID'),
            'timeout' => 30,
        ],

        'amazon' => [
            'enabled' => env('AMAZON_TRANSLATE_ENABLED', false),
            'display_name' => 'Amazon Translate',
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'timeout' => 30,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Service
    |--------------------------------------------------------------------------
    |
    | If the default service fails, you can specify a fallback service here.
    | Set to null to disable fallback.
    |
    */

    'fallback' => env('TRANSLATION_FALLBACK_SERVICE', null),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Enable caching of translations to reduce API calls and costs.
    |
    */

    'cache' => [
        'enabled' => env('TRANSLATION_CACHE_ENABLED', true),
        'ttl' => env('TRANSLATION_CACHE_TTL', 86400), // 24 hours in seconds
        'prefix' => 'translation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of translation requests and errors.
    |
    */

    'logging' => [
        'enabled' => env('TRANSLATION_LOGGING_ENABLED', true),
        'channel' => env('TRANSLATION_LOG_CHANNEL', 'stack'),
    ],

];
