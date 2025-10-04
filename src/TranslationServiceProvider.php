<?php

namespace Fastnet\TranslationServices;

use Illuminate\Support\ServiceProvider;
use Fastnet\TranslationServices\TranslationManager;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/translation-services.php',
            'translation-services'
        );

        // Register the main translation manager
        $this->app->singleton('translator_service', function ($app) {
            return new TranslationManager(config('translation-services'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/translation-services.php' => config_path('translation-services.php'),
            ], 'translation-services-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        // Must match the key used in register()
        return ['translator_service', TranslationManager::class];
    }
}
