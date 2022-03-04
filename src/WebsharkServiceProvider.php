<?php

namespace Apampolino\Webshark;

use Illuminate\Support\ServiceProvider;
use Apampolino\Webshark\Contracts\WiresharkClientInterface;
use Apampolino\Webshark\SharkdClient;

class WebsharkServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Apampolino\Webshark\Contracts\WiresharkClientInterface', function($app) {
            $host = env('WEBSHARK_HOST', '127.0.0.1');
            $port = env('WEBSHARK_HOST_PORT', 4446);
            return new SharkdClient($host, $port);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([]);
        }

        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'webshark');

        $this->mergeConfigFrom(
            __DIR__. '/config/filesystems.php', 'filesystems.disks'
        );

        $this->publishes([
            __DIR__ . '/config/webshark.php' => config_path('webshark.php'),
        ], 'webshark-config');

        $this->publishes([
            __DIR__ . '/views' => resource_path('views/vendor/webshark'),
        ], 'webshark-views');

        $this->publishes([
            __DIR__ . '/public' => public_path('vendor/webshark'),
        ], 'webshark-public');
    }
}