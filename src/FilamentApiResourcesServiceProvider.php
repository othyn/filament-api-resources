<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Othyn\FilamentApiResources\Livewire\Synthesizers\ApiModelSynthesizer;
use Othyn\FilamentApiResources\Services\ApiService;

class FilamentApiResourcesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-api-resources.php',
            'filament-api-resources'
        );

        $this->app->singleton(ApiService::class, function ($app) {
            return new ApiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Livewire synthesizer
        Livewire::propertySynthesizer(ApiModelSynthesizer::class);

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/filament-api-resources.php' => config_path('filament-api-resources.php'),
            ], 'filament-api-resources-config');
        }
    }
}
