<?php

namespace Miguilim\LaravelSinglestoreBackup;

use Illuminate\Support\ServiceProvider;

class LaravelSinglestoreBackupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/singlestore-backup.php', 'singlestore-backup');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureCommands();
        $this->configurePublishing();
    }

    /**
     * Configure publishing for the package.
     */
    protected function configurePublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/singlestore-backup.php' => config_path('singlestore-backup.php'),
        ], 'singlestore-backup-config');
    }

    /**
     * Configure the commands offered by the application.
     */
    protected function configureCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            Console\SinglestoreBackupCommand::class,
        ]);
    }
}
