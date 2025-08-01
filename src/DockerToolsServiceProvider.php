<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools;

use Illuminate\Support\ServiceProvider;
use Inisiatif\DockerTools\Console\PublishCommand;
use Illuminate\Contracts\Support\DeferrableProvider;

class DockerToolsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->extend('Illuminate\Foundation\Console\VendorPublishCommand', function ($command, $app) {
            return new PublishCommandInterceptor($app['files']);
        });

        // Bind the PublishCommand
        $this->app->bind(PublishCommand::class);
    }

    public function boot(): void
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishCommand::class,
            ]);
        }
    }

    protected function configurePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish GitHub workflows and configuration
            $this->publishes([
                __DIR__ . '/../stubs/github' => $this->app->basePath('.github'),
            ], 'doctool-stubs');

            // Publish Docker configuration
            $this->publishes([
                __DIR__ . '/../stubs/docker' => $this->app->basePath('docker'),
            ], 'doctool-stubs');

            // Publish docker-compose files
            $this->publishes([
                __DIR__ . '/../stubs/docker-compose.yml' => $this->app->basePath('docker-compose.yml'),
                __DIR__ . '/../stubs/docker-compose.prod.yml' => $this->app->basePath('docker-compose.prod.yml'),
            ], 'doctool-stubs');
        }
    }

    public function provides(): array
    {
        return [
            PublishCommand::class,
        ];
    }
}
