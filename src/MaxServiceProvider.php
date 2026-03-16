<?php

namespace Blacky0892\Max;

use Blacky0892\Max\Console\Commands\MakeWebhookHandlerCommand;
use Blacky0892\Max\Console\Commands\RegisterWebhookCommand;
use Blacky0892\Max\Services\CallbackService;
use Blacky0892\Max\Services\SubscriptionService;
use Illuminate\Support\ServiceProvider;

class MaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/max.php', 'max');

        $this->app->singleton(MaxApiClient::class, function () {
            return new MaxApiClient();
        });

        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService($app->make(MaxApiClient::class));
        });

        $this->app->singleton(CallbackService::class, function ($app) {
            return new CallbackService($app->make(MaxApiClient::class));
        });

        $this->app->singleton('max', function ($app) {
            return new MaxManager(
                $app->make(MaxApiClient::class),
                $app->make(SubscriptionService::class),
                $app->make(CallbackService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/max.php' => config_path('max.php'),
        ], 'max-config');

        $this->publishes([
            __DIR__ . '/../stubs/MaxWebhookHandler.php.stub' => app_path('Services/Max/MaxWebhookHandler.php'),
        ], 'max-handler');

        if (config('max.route.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/max.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeWebhookHandlerCommand::class,
                RegisterWebhookCommand::class,
            ]);
        }
    }
}