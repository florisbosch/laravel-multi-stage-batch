<?php

namespace Florisbosch\MultiStageBatch;

use Illuminate\Support\ServiceProvider;

class MultiStageBatchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/multi-stage-batch.php' => config_path('multi-stage-batch.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/multi-stage-batch.php', 'multi-stage-batch');
    }
}
