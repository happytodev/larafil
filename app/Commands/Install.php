<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use LaravelZero\Framework\Commands\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install
                            {--create-user}
                            {--serve}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel and Filament.' . PHP_EOL .
                              '               Options:' . PHP_EOL .
                              '                  * --create-user: Whether an user should be created' . PHP_EOL .
                              '                  * --serve: Whether the integrated server should be run';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ask for application name
        $applicationName = $this->ask('Application name');

        // Create Laravel Project
        $this->info("Creating Laravel project '{$applicationName}'...");
        $exresult = passthru('composer create-project laravel/laravel ' . $applicationName);

        // Move to project folder
        chdir($applicationName);

        // Install Filament
        $this->info("Installing Filament...");
        passthru('composer require filament/filament:^3.2 -W');
        passthru('php artisan filament:install --panels --no-interaction');

        // Check if we need to create an user
        if ($this->hasOption('create-user') && $this->option('create-user') === true) {
            $this->info("Creating a filament user...");
            passthru('php artisan make:filament-user');
        }

        // Check if we need to start server
        if ($this->hasOption('serve') && $this->option('serve') === true) {
            $this->info("Starting the integrated server...");
            passthru('php artisan serve');
        }

        // Tell user all is done !
        $this->info("Installation done !");
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
    }
}
