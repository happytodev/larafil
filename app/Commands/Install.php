<?php

namespace App\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
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
                            {--filament-url=admin}
                            {--laravel-version=current}
                            {--mysql}
                            {--serve}';

    protected string $applicationName;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel and Filament.' . PHP_EOL .
        '               Options:' . PHP_EOL .
        '                  * --create-user: Whether an user should be created' . PHP_EOL .
        '                  * --filament-url: Choose the url for FilamentPHP admin' . PHP_EOL .
        "                  * --laravel-version: Choose the version of Laravel you want to install. The possible options are 'previous', 'current'" . PHP_EOL .
        '                  * --mysql: Whether a mysql database has to be used instead the sqlite default one (Do not use with l10 option)' . PHP_EOL .
        '                  * --serve: Whether the integrated server should be run';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ask for application name
        $this->applicationName = $this->ask('Application name');

        if ($this->hasOption('laravel-version')) {
            try {
                match ($this->option('laravel-version')) {
                    'previous' => $this->installPreviousLaravel(),
                    'current', '' => $this->installCurrentLaravel(),
                    // 'dev' => $this->installDevLaravel(),
                    'default' => $this->installCurrentLaravel()
                };    //code...
            } catch (\UnhandledMatchError $e) {
                $this->error("You can't use other option than 'previous' or 'current'");
                exit(1);
            }
        } else 
        {
            // Install current Laravel version by default
            $this->installCurrentLaravel();
        }

        // Check if a mysql database need to be installed instead the sqlite default one
        if ($this->hasOption('mysql') && $this->option('mysql') === true) {
            $this->info("Change configuration in your .env file");
            $this->configureDatabase($this->applicationName);

            $this->info("Creating the mysql database...");
            $this->createDatabase($this->applicationName);

            $this->info("Running the migration...");
            $this->runMigrations();
            
            $this->info("Removing sqlite database...");
            $this->deleteSqliteDatabase();
        }

        // Install Filament
        $this->info("Installing Filament...");
        passthru('composer require filament/filament:^3.2 -W');
        passthru('php artisan filament:install --panels --no-interaction');

        if ($this->hasOption('filament-url')) {
            // @todo : add check on url string
            $this->setFilamentAdminUrl($this->option('filament-url'));
        }

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

    private function configureDatabase(string $databaseName)
    {
        $dbHost = '127.0.0.1';
        $dbName = "$databaseName";
        $dbUser = 'root';
        $dbPassword = '';

        file_put_contents('.env', str_replace(
            [
                'DB_CONNECTION=sqlite',
                '# DB_HOST=127.0.0.1',
                '# DB_PORT=3306',
                '# DB_DATABASE=laravel',
                '# DB_USERNAME=root',
                '# DB_PASSWORD=',
            ],
            [
                'DB_CONNECTION=mysql',
                'DB_HOST=' . $dbHost,
                'DB_PORT=3306',
                'DB_DATABASE=' . $dbName,
                'DB_USERNAME=' . $dbUser,
                'DB_PASSWORD=' . $dbPassword,
            ],
            file_get_contents('.env')
        ));
    }

    // Replace database name 'laravel' by app name
    private function configureDatabaseL10(string $databaseName)
    {
        $dbName = "$databaseName";

        file_put_contents('.env', str_replace(
            [
                'DB_DATABASE=laravel',
            ],
            [
                'DB_DATABASE=' . $dbName,
            ],
            file_get_contents('.env')
        ));
    }

    private function createDatabase()
    {
        passthru('php artisan config:cache');
        passthru('php artisan migrate --force');
    }

    private function deleteSqliteDatabase()
    {
        if (file_exists('database/database.sqlite')) {
            unlink('database/database.sqlite');
        }
    }

    private function installPreviousLaravel()
    {
        $this->info('You choose to install previous version of Laravel');

        // detect if user uses 'l10' and 'mysql' together which are incompatibles
        if ($this->hasOption('mysql') && $this->option('mysql') === true) {
            $this->error("You can't use `l10` and `mysql` options together because Laravel 10 use by default mysql connection");
            exit(1);
        }

        $this->info("Creating Laravel 10 project '{$this->applicationName}'...");
        $exresult = passthru('composer create-project laravel/laravel:10 ' . $this->applicationName);

        chdir($this->applicationName);
        
        // Creating the mysql database
        $this->info("Change configuration in your .env file");
        $this->configureDatabaseL10($this->applicationName);
        
        $this->info("Creating the mysql database...");
        $this->createDatabase($this->applicationName);
        
        $this->info("Running the migration...");    
        $this->runMigrations();
    }

    private function installCurrentLaravel()
    {
        $this->info('You choose to install the current version of Laravel');

        $this->info("Creating Laravel project '{$this->applicationName}'...");
        $exresult = passthru('composer create-project laravel/laravel ' . $this->applicationName);

        // Move to project folder
        chdir($this->applicationName);
    }

    // private function installDevLaravel()
    // {
    //     $this->info('You choose to install the development version of Laravel');

    //     $this->info("Creating Laravel project '{$this->applicationName}'...");
    //     $exresult = passthru('composer create-project laravel/laravel ' . $this->applicationName . ' dev-master');

    //     // Move to project folder
    //     chdir($this->applicationName);
    // }

    private function runMigrations()
    {
        passthru('php artisan migrate');
    }


    private function setFilamentAdminUrl(string $url)
    {
        file_put_contents('app/Providers/Filament/AdminPanelProvider.php', str_replace(
            [
                "->path('admin')"
            ],
            [
                "->path('$url')"
            ],
            file_get_contents('app/Providers/Filament/AdminPanelProvider.php')
        ));
    }
}
