<?php

declare(strict_types=1);

namespace Milenmk\LaravelGdprExporter;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class GDPRExporterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-gdpr-exporter');

        // Publish config
        $this->publishes(
            [
                __DIR__ . '/../config/gdpr-exporter.php' => config_path('gdpr-exporter.php'),
            ],
            'laravel-gdpr-exporter-config',
        );

        // Publish views
        $this->publishes(
            [
                __DIR__ . '/../resources/views/livewire' => base_path(
                    'resources/views/vendor/laravel-gdpr-exporter/livewire',
                ),
            ],
            'laravel-gdpr-exporter-views',
        );

        Livewire::component('laravel-gdpr-exporter', GDPRComponent::class);
    }

    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/gdpr-exporter.php', 'gdpr-exporter');
    }
}
