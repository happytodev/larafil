<?php

namespace App\Commands;

use PDO;
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
                            {name?}
                            {--create-user}
                            {--filament-url=admin}
                            {--laravel-version=current}
                            {--mysql}
                            {--serve}';

    // To store application name
    protected string $applicationName;

    // To store directory name. It is the application name cleaned
    protected string $directoryName;

    // Store db_connection
    protected string $db_connection = 'sqlite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel and Filament.' . PHP_EOL .
        '               Argument:' . PHP_EOL .
        '                  * name: You can provide directly the name of the application. If not, Larafil ask you for it' . PHP_EOL .
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
        // Get name from argument if provided
        if ($this->hasArgument('name') && $this->argument('name') !== null) {
            $this->applicationName = $this->argument('name');
        } else {
            // Ask for application name
            $this->applicationName = $this->ask('Application name');
        }

        // Check application name and clean it if necessary
        $this->checkApplicationName();

        if ($this->hasOption('laravel-version')) {
            try {
                match ($this->option('laravel-version')) {
                    'previous' => $this->installPreviousLaravel(),
                    'current', '' => $this->installCurrentLaravel(),
                    // 'dev' => $this->installDevLaravel(),
                    'default' => $this->installCurrentLaravel()
                };    
            } catch (\UnhandledMatchError $e) {
                $this->error("You can't use other option than 'previous' or 'current'");
                exit(1);
            }
        } else {
            // Install current Laravel version by default
            $this->installCurrentLaravel();
        }

        // Check if a mysql database need to be installed instead the sqlite default one
        if ($this->hasOption('mysql') && $this->option('mysql') === true) {
            $this->db_connection = 'mysql';

            $this->info("Change configuration in your .env file");
            $this->configureDatabase($this->directoryName);

            $this->info("Creating the mysql database...");
            $this->createDatabase($this->applicationName);

            $this->info("Running the migration...");
            $this->runMigrations();

            $this->info("Removing sqlite database...");
            $this->deleteSqliteDatabase();
        }

        // Install Filament
        $this->info("Installing Filament...");
        passthru('composer require -q filament/filament:^3.2 -W');
        passthru('php artisan filament:install --scaffold --panels --no-interaction');
        passthru('npm install --quiet');

        if ($this->hasOption('filament-url')) {
            // @todo : add check on url string
            $this->setFilamentAdminUrl($this->option('filament-url'));
        }

        // Check if we need to create an user
        if ($this->hasOption('create-user') && $this->option('create-user') === true) {
            $this->info("Creating a filament user...");
            passthru('php artisan make:filament-user');
        }

        // Ask user if he wants install some filament plugin
        $plugins = $this->choice(
            'What plugin do you want to install (if many, separate them by a comma) ?',
            ['None (default)', 'Breezy', 'Curator', 'Shield', 'Spatie Role Permissions'],
            default: 0,
            multiple: true
        );

        $this->checkAndInstallPlugins($plugins);


        // Check if we need to start server
        if ($this->hasOption('serve') && $this->option('serve') === true) {
            $this->info("Starting the integrated server...");
            passthru('php artisan serve');
        }

        // Tell user all is done !
        $this->info("Installation done ! 🎉");
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
    }

    private function checkAndInstallPlugins(array $plugins)
    {
        $choosenPlugins = collect($plugins);

        $availablePlugins = collect([
            'Breezy' => [
                // https://filamentphp.com/plugins/jeffgreco-breezy#installation
                'name' => 'Breezy',
                'instructions' => [
                    '1' => [
                        'type' => 'bash',
                        'value' => 'composer require jeffgreco13/filament-breezy -q'
                    ],
                    '2' => [
                        'type' => 'bash',
                        'value' => 'php artisan breezy:install'
                    ]
                ]
            ],
            'Curator' => [
                // https://filamentphp.com/plugins/awcodes-curator#installation
                'name' => 'Curator',
                'instructions' => [
                    '1' => [
                        'type' => 'bash',
                        'value' => 'composer require awcodes/filament-curator -q'
                    ],
                    '2' => [
                        'type' => 'bash',
                        'value' => 'php artisan curator:install'
                    ],
                    '3' => [
                        'type' => 'bash',
                        'value' => 'npm install -D cropperjs'
                    ],
                    '4' => [
                        'type' => 'insert',
                        'file' => 'resources/css/app.css',
                        'search' => '@tailwind utilities;',
                        'replace' =>
                        "@tailwind utilities;

@import 'node-modules/cropperjs/dist/cropper.css';
@import 'vendor/awcodes/filament-curator/resources/css/plugin.css';"
                    ],
                    '5' => [
                        'type' => 'insert',
                        'file' => 'tailwind.config.js',
                        'search' => 'content: [',
                        'replace' => "content: [
        './vendor/awcodes/filament-curator/resources/**/*.blade.php',"
                    ],

                ]
            ],
            'Shield' => [
                // https://filamentphp.com/plugins/bezhansalleh-shield#installation
                'name' => 'Shield',
                'instructions' => [
                    '1' => [
                        'type' => 'bash',
                        'value' => 'composer require bezhansalleh/filament-shield -q'
                    ],
                    '2' => [

                        'type' => 'insert',
                        'file' => 'app/Models/User.php',
                        'search' => '
class User extends Authenticatable
{',
                        'replace' =>
                        'use Spatie\Permission\Traits\HasRoles;
     
class User extends Authenticatable
{
    use HasRoles;'
                    ],
                    '3' => [

                        'type' => 'bash',
                        'value' => 'php artisan vendor:publish --tag=filament-shield-config',
                    ],
                    '4' => [
                        'type' => 'insert',
                        'file' => 'app/Providers/Filament/AdminPanelProvider.php',
                        'search' => '->plugins([',
                        'replace' =>
                        '->plugins([
                                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),'
                    ],
                    '5' => [
                        'type' => 'bash',
                        'value' => 'php artisan shield:install'
                    ]
                ]
            ],
            'Spatie Role Permissions' => [
                // https://filamentphp.com/plugins/tharinda-rodrigo-spatie-roles-permissions#installation
                'name' => 'Spatie Role Permissions',
                'instructions' => [
                    '1' => [
                        'type' => 'bash',
                        'value' => 'composer require althinect/filament-spatie-roles-permissions -q',
                    ],
                    '2' => [
                        'type' => 'bash',
                        'value' => 'php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"',
                    ],
                    '3' => [
                        'type' => 'insert',
                        'file' => 'app/Providers/Filament/AdminPanelProvider.php',
                        'search' => '
class AdminPanelProvider extends PanelProvider',
                        'replace' =>
                        'use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;

class AdminPanelProvider extends PanelProvider'
                    ],
                    '4' => [
                        'type' => 'insert',
                        'file' => 'app/Providers/Filament/AdminPanelProvider.php',
                        'search' => '->plugins([',
                        'replace' =>
                        '->plugins([
                            FilamentSpatieRolesPermissionsPlugin::make(),'
                    ]
                ],
            ]
        ]);


        // check if none is in choosen plugins
        if ($choosenPlugins->contains('None (default)')) {
            $this->info('You choosen to install none plugins');
            return;
        }

        // Insert in AdminPanelProvider the plugin functionnality
        // We do that before installing the selected plugins
        // to simplify the implementation
        $adminPanelProviderFile = 'app/Providers/Filament/AdminPanelProvider.php';

        $this->initializeFileForSearchAndReplace(
            $adminPanelProviderFile,
            ']);',
            '])
            ->plugins([
            ]);'
        );

        $bar = $this->output->createProgressBar($choosenPlugins->count());

        $bar->start();

        // Get only choosen plugins
        $pluginsToExecute = $availablePlugins->only($choosenPlugins);

        $pluginsToExecute->each(function ($plugin) {
            // Check if plugins has instructions
            if (isset($plugin['instructions'])) {
                $this->info('Starting install plugin : ' . $plugin['name']);

                // Go through the instructions and execute them one by one with passthru()
                collect($plugin['instructions'])->each(function ($instruction, $type) {
                    if ($instruction['type'] === 'bash') {
                        passthru($instruction['value']);
                    }
                    if ($instruction['type'] === 'insert') {
                        $this->initializeFileForSearchAndReplace(
                            $instruction['file'],
                            $instruction['search'],
                            $instruction['replace']
                        );
                    }
                });
            }
        });

        $bar->finish();
    }

    /**
     * Check application name
     *
     * @return void
     */
    private function checkApplicationName(): void
    {
        // Check that the application name is correct
        if (!preg_match('/^[a-zA-Z]/', $this->applicationName)) {
            $this->error("Le nom de l'application doit commencer par une lettre.");
            exit(1); // Stop execution in the event of an error
        }

        // If application name contains unauthorised characters we will
        // encapsulated the application name with double quotes
        if (!preg_match('/^[a-zA-Z0-9\s\']+$/u', $this->applicationName)) {
            $this->applicationName = '"' . $this->applicationName . '"';
        }

        // Replace special characters while preserving accented characters
        $this->directoryName = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $this->applicationName));
        $this->directoryName = preg_replace('/[^a-zA-Z0-9]/', '', $this->directoryName);

        if (file_exists($this->directoryName)) {
            $this->error("The directory for the application already exists. Please choose another name.");
            exit(1); // Stop execution in the event of an error
        }
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

    // Set application name in .env file
    private function configureEnvAppName(string $applicationName): void {
        $this->initializeFileForSearchAndReplace(
            '.env',
            'APP_NAME=Laravel',
            'APP_NAME=' . $applicationName
        );
    }

    private function createDatabase()
    {
        if ($this->databaseExists($this->directoryName, $this->db_connection)) {
            $this->error("Database '$this->directoryName' already exists.");
            exit(1); // Stop execution in the event of an error
        }

        passthru('php artisan config:cache');
        passthru('php artisan migrate --force');
    }


    private function databaseExists($databaseName, $connection)
    {
        if ($connection === 'sqlite') {
            // check if sqlite already exists. Normally this use case is impossible
            // because we check the directory name of application does'nt exists
            return file_exists('database/database.sqlite');
        } elseif ($connection === 'mysql') {
             $pdo = new PDO("mysql:host=127.0.0.1", "root", "");

           // Preparing the SQL query to list all the databases
            $statement = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :databaseName");

            // Execute the query with the database name as parameter
            $statement->execute(['databaseName' => $databaseName]);

            // Retrieving results
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            // Closing the PDO connection
            $pdo = null;

            // Check whether the database already exists
            return $result !== false;
        } else {
            // Database type not supported
            return false;
        }
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

        $this->info("Creating Laravel 10 project '{$this->directoryName}'...");
        $exresult = passthru('composer create-project laravel/laravel:10 ' . $this->directoryName);

        chdir($this->directoryName);

        // Creating the mysql database
        $this->info("Change configuration in your .env file");
        $this->configureDatabaseL10($this->directoryName);
        $this->configureEnvAppName($this->applicationName);

        $this->info("Creating the mysql database...");
        $this->createDatabase($this->directoryName);

        $this->info("Running the migration...");
        $this->runMigrations();
    }

    private function installCurrentLaravel()
    {
        $this->info('You choose to install the current version of Laravel');

        $this->info("Creating Laravel project '{$this->directoryName}'...");
        $exresult = passthru('composer create-project -q laravel/laravel ' . $this->directoryName);

        // Move to project folder
        chdir($this->directoryName);

        $this->configureEnvAppName($this->applicationName);
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

    // Some files need to have a marker to start search, especially when the
    // file is originally empty
    private function initializeFileForSearchAndReplace(string $fileName, string $search, string $replace)
    {
        $content = file_get_contents($fileName);
        if (!empty($content)) {
            file_put_contents($fileName, str_replace([$search], [$replace], $content));
        } else {
            // Handle the empty file scenario (optional)
            // You could add the marker directly using file_put_contents
            file_put_contents($fileName, $replace);
        }
    }


    private function setFilamentAdminUrl(string $url)
    {
        $this->initializeFileForSearchAndReplace(
            'app/Providers/Filament/AdminPanelProvider.php',
            "->path('admin')",
            "->path('$url')"
        );
    }
}
