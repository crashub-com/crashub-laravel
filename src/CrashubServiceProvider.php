<?php

namespace Crashub;

use Illuminate\Support\ServiceProvider;
use Crashub\Commands\Install;
use GuzzleHttp\Client;

class CrashubServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('crashub', function ($app) {
            return new CrashubClient(new Client(['http_errors' => false]));
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/crashub.php' => config_path('crashub.php'),
        ], 'crashub-config');
    }
}
