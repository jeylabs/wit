<?php

namespace Jeylabs\Wit\Laravel;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Jeylabs\Wit\Wit;
use Laravel\Lumen\Application as LumenApplication;

class WitServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->setupConfig($this->app);
    }

    protected function setupConfig(Application $app)
    {
        $source = __DIR__.'/config/wit.php';

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => config_path('wit.php')]);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('wit');
        }

        $this->mergeConfigFrom($source, 'wit');
    }

    public function register()
    {
        $this->registerBindings($this->app);
    }

    protected function registerBindings(Application $app)
    {
        $app->singleton('wit', function ($app) {
            $config = $app['config'];

            return new Wit(
                $config->get('wit.access_token', null),
                $config->get('wit.async_requests', false)
            );
        });

        $app->alias('wit', Wit::class);
    }

    public function provides()
    {
        return ['wit'];
    }
}
