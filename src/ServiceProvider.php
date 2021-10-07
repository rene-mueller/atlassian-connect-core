<?php

namespace AtlassianConnectCore;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Class ServiceProvider
 *
 * @package AtlassianConnectCore
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadPublishes();
        $this->loadRoutes();
        $this->loadMigrations();
        $this->loadConsoleCommands();
        $this->loadViews();
        $this->loadWebhooks();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/connect.php', 'connect'
        );

        $this->registerFacades();
        $this->registerJWTGuard();
    }

    /**
     * Load publishes
     */
    protected function loadPublishes()
    {
        $this->publishes([__DIR__ . '/../config/connect.php' => config_path('connect.php')], 'config');
        $this->publishes([__DIR__ . '/../resources/views' => resource_path('views/vendor/connect')], 'views');
    }

    /**
     * Load routes
     */
    protected function loadRoutes()
    {
        if(config('connect.loadRoutes')) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }

        Route::getRoutes()->refreshNameLookups();
    }

    /**
     * Load migrations
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load console commands
     */
    protected function loadConsoleCommands()
    {
        if(!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            \AtlassianConnectCore\Console\InstallCommand::class,
            \AtlassianConnectCore\Console\DummyCommand::class
        ]);
    }

    /**
     * Load views
     */
    protected function loadViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'connect');
    }

    /**
     * Load webhook listeners
     */
    protected function loadWebhooks()
    {
        foreach (config('connect.webhooks', []) as $webhook => $listener) {
            \AtlassianConnectCore\Facades\Webhook::listen($webhook, $listener);
        }
    }

    /**
     * Register package facades
     */
    protected function registerFacades()
    {
        $this->app->bind('descriptor', Descriptor::class);
        $this->app->bind('webhook', Webhook::class);
    }

    /**
     * Register JWT guard
     */
    protected function registerJWTGuard()
    {
        Auth::extend(config('connect.guard'), function (Application $app, $name, array $config)
        {
            return $app->makeWith(JWTGuard::class, [
                'provider' => Auth::createUserProvider($config['provider']),
                'session' => $app['session.store'],
                'cookie' => $app['cookie']
            ]);
        });
    }
}
