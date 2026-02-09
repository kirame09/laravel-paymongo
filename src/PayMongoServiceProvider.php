<?php

namespace Kirame\PayMongo;

use Illuminate\Support\ServiceProvider;

class PayMongoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/paymongo.php', 'paymongo');

        $this->app->singleton(PayMongo::class, function ($app) {
            return new PayMongo(
                secretKey: config('paymongo.secret_key', ''),
                baseUrl: config('paymongo.base_url', 'https://api.paymongo.com/v1'),
                timeout: config('paymongo.timeout', 15),
                retries: config('paymongo.retries', 2),
            );
        });

        $this->app->singleton(WebhookVerifier::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/paymongo.php' => config_path('paymongo.php'),
            ], 'paymongo-config');
        }
    }
}
