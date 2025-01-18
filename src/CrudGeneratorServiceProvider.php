<?php

namespace Rahulp\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Rahulp\CrudGenerator\Commands\CreateModelCommand;
use Rahulp\CrudGenerator\Commands\ProjectSetupCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register config file
        $this->mergeConfigFrom(
            __DIR__.'/config/repository.php', 'repository'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateModelCommand::class,
                ProjectSetupCommand::class
            ]);

            // Publish config file
            $this->publishes([
                __DIR__.'/config/repository.php' => config_path('repository.php'),
            ], 'repository-config');
        }
    }
}
