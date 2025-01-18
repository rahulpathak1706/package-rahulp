<?php

namespace Rahulp\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProjectSetupCommand extends Command
{
    protected $signature = 'project:setup';

    protected $description = 'Setup project with required middleware and configurations';

    public function handle()
    {
        $this->createDBTransactionMiddleware();
        $this->registerMiddleware();
        $this->createHelperFile();
        $this->registerHelper();

        $this->info('Project setup completed successfully!');
    }

    protected function createDBTransactionMiddleware()
    {
        $middlewarePath = app_path('Http/Middleware/DBTransaction.php');

        if (!File::exists($middlewarePath)) {
            // Create middleware directory if it doesn't exist
            if (!File::isDirectory(app_path('Http/Middleware'))) {
                File::makeDirectory(app_path('Http/Middleware'), 0755, true);
            }

            // Get stub content
            $stub = File::get(__DIR__.'/../stubs/middleware/dbtransaction.stub');

            File::put($middlewarePath, $stub);
            $this->info('DBTransaction middleware created successfully.');
        } else {
            $this->info('DBTransaction middleware already exists.');
        }
    }

    protected function registerMiddleware()
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!File::exists($bootstrapPath)) {
            $this->error('bootstrap/app.php not found!');
            return;
        }

        $content = File::get($bootstrapPath);

        // Check if middleware is already registered
        if (str_contains($content, 'DBTransaction::class')) {
            $this->info('Middleware already registered in bootstrap/app.php');
            return;
        }

        // Find the empty middleware configuration
        $pattern = '/->withMiddleware\(function \(Middleware \$middleware\) \{\s*\/\/\s*\}\)/s';

        $middlewareConfig = <<<'EOT'
->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \App\Http\Middleware\DBTransaction::class
        ]);
    })
EOT;

        // Replace the empty middleware configuration
        $content = preg_replace($pattern, $middlewareConfig, $content);

        File::put($bootstrapPath, $content);
        $this->info('Middleware registered in bootstrap/app.php successfully.');
    }

    protected function createHelperFile()
    {
        // Create helpers directory if it doesn't exist
        $helperDir = app_path('Helpers');
        if (!File::isDirectory($helperDir)) {
            File::makeDirectory($helperDir, 0755, true);
        }

        $helperPath = app_path('Helpers/functions.php');

        if (!File::exists($helperPath)) {
            $stub = File::get(__DIR__.'/../stubs/helper/functions.stub');
            File::put($helperPath, $stub);
            $this->info('Helper functions created successfully.');
        } else {
            $this->info('Helper functions already exist.');
        }
    }

    protected function registerHelper()
    {
        $composerPath = base_path('composer.json');

        if (!File::exists($composerPath)) {
            $this->error('composer.json not found!');
            return;
        }

        $composerJson = json_decode(File::get($composerPath), true);

        // Add autoload file if not exists
        if (!isset($composerJson['autoload']['files']) ||
            !in_array('app/Helpers/functions.php', $composerJson['autoload']['files'])) {

            if (!isset($composerJson['autoload']['files'])) {
                $composerJson['autoload']['files'] = [];
            }

            $composerJson['autoload']['files'][] = 'app/Helpers/functions.php';

            // Save updated composer.json
            File::put(
                $composerPath,
                json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->info('Helper functions registered in composer.json');
            $this->info('Please run "composer dump-autoload" to complete the setup.');
        } else {
            $this->info('Helper functions already registered in composer.json');
        }
    }
}
