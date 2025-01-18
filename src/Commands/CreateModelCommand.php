<?php

namespace Rahulp\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateModelCommand extends Command
{
    protected $signature = 'make:crud {model} {columns?*}';

    protected $description = 'Create CRUD operations with custom model and columns';

    public function handle()
    {
        $model = $this->argument('model');
        $columns = $this->argument('columns');

        if (empty($columns)) {
            $this->error('No columns specified. Use format: name:string:required content:text:nullable is_active:boolean:required:true');
            return;
        }

        // Create necessary directories
        $this->createDirectories();

        $this->createMigration($model, $columns);
        $this->createModel($model, $columns);
        $this->createInterface($model);
        $this->createRepository($model, $columns);
        $this->createService($model);
        $this->createController($model, $columns);
        $this->createRepositoryProvider($model);

        $this->info("CRUD operations generated successfully for {$model}!");
    }

    protected function createDirectories()
    {
        $directories = [
            app_path('Repositories'),
            app_path('Repositories/Interfaces'),
            app_path('Services'),
            app_path('Providers'),
        ];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
        }
    }

    protected function createRepositoryProvider($model)
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (!File::exists($providerPath)) {
            // Create new provider if it doesn't exist
            $stub = File::get(__DIR__.'/../stubs/provider/repository.stub');
            File::put($providerPath, $stub);
        }

        // Get current content
        $content = File::get($providerPath);

        // Add use statements if they don't exist
        $useStatements = [
            "use App\\Repositories\\{$model}Repository;",
            "use App\\Repositories\\Interfaces\\{$model}RepositoryInterface;"
        ];

        // Find the last use statement or namespace
        $lastUsePos = strrpos($content, "use ");
        $namespacePos = strpos($content, "namespace ");

        if ($lastUsePos !== false) {
            // If there are existing use statements, add after the last one
            $insertPos = strpos($content, ";", $lastUsePos) + 1;
            $newUses = "";
            foreach ($useStatements as $statement) {
                if (!str_contains($content, $statement)) {
                    $newUses .= "\n" . $statement;
                }
            }
            if (!empty($newUses)) {
                $content = substr_replace($content, $newUses, $insertPos, 0);
            }
        } else {
            // If no use statements exist, add after namespace
            $insertPos = strpos($content, ";", $namespacePos) + 1;
            $newUses = "\n";
            foreach ($useStatements as $statement) {
                $newUses .= "\n" . $statement;
            }
            $content = substr_replace($content, $newUses, $insertPos, 0);
        }

        // Add binding if it doesn't exist
        $binding = "\$this->app->bind({$model}RepositoryInterface::class, {$model}Repository::class);";
        if (!str_contains($content, $binding)) {
            $pattern = "/public function register\(\): void\s*\{\s*/";
            $replacement = "public function register(): void\n    {\n        $binding\n";
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Save updated content
        File::put($providerPath, $content);

        // Register provider in config/app.php if not already registered
        $this->registerProviderInConfig();

        $this->info("RepositoryServiceProvider updated successfully!");
    }

    protected function registerProviderInConfig()
    {
        $configPath = config_path('app.php');
        $content = File::get($configPath);

        $providerClass = 'App\\Providers\\RepositoryServiceProvider::class';

        if (!str_contains($content, $providerClass)) {
            $content = str_replace(
                "'providers' => ServiceProvider::defaultProviders()->merge([",
                "'providers' => ServiceProvider::defaultProviders()->merge([\n        " . $providerClass . ",",
                $content
            );
            File::put($configPath, $content);
        }
    }

    protected function createInterface($model)
    {
        $interfacePath = app_path("Repositories/Interfaces/{$model}RepositoryInterface.php");
        $stub = File::get(__DIR__.'/../stubs/interface/repository.stub');

        $stub = str_replace(
            ['{{modelName}}', '{{modelVariable}}'],
            [$model, lcfirst($model)],
            $stub
        );

        File::put($interfacePath, $stub);
        $this->info('Interface created successfully.');
    }

    protected function createRepository($model, $columns)
    {
        $repositoryPath = app_path("Repositories/{$model}Repository.php");
        $stub = File::get(__DIR__.'/../stubs/repository/repository.stub');

        $searchFields = $this->generateSearchFields($columns);

        $stub = str_replace(
            [
                '{{modelName}}',
                '{{modelVariable}}',
                '{{searchFields}}'
            ],
            [
                $model,
                lcfirst($model),
                $searchFields
            ],
            $stub
        );

        File::put($repositoryPath, $stub);
        $this->info('Repository created successfully.');
    }

    protected function createService($model)
    {
        $servicePath = app_path("Services/{$model}Service.php");
        $stub = File::get(__DIR__.'/../stubs/service/service.stub');

        $stub = str_replace(
            ['{{modelName}}', '{{modelVariable}}'],
            [$model, lcfirst($model)],
            $stub
        );

        File::put($servicePath, $stub);
        $this->info('Service created successfully.');
    }

    protected function generateSearchFields($columns): string
    {
        $fields = [];
        foreach ($columns as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = $parts[1] ?? 'string';

            if (in_array($type, ['string', 'text'])) {
                $fields[] = "\$query->orWhere('$name', 'like', '%' . \$search . '%');";
            }
        }

        return empty($fields) ?
            '$query->where("id", "like", "%". $search ."%");' :
            implode("\n                    ", $fields);
    }

    protected function createMigration($model, $columns)
    {
        $tableName = Str::plural(Str::snake($model));
        $migrationPath = database_path('migrations/'.date('Y_m_d_His').'_create_'.$tableName.'_table.php');
        $stub = File::get(__DIR__.'/../stubs/migration.stub');

        $fields = $this->generateMigrationFields($columns);
        $stub = str_replace(
            ['{{modelPlural}}', '{{fields}}'],
            [$tableName, $fields],
            $stub
        );

        File::put($migrationPath, $stub);
        $this->info('Migration created successfully.');
    }

    protected function createModel($model, $columns)
    {
        $modelPath = app_path('Models/'.$model.'.php');
        $stub = File::get(__DIR__.'/../stubs/model.stub');

        $fillable = $this->generateFillableFields($columns);
        $defaults = $this->generateDefaultValues($columns);

        $stub = str_replace(
            ['{{modelName}}', '{{fillable}}', '{{defaults}}'],
            [$model, $fillable, $defaults],
            $stub
        );

        File::put($modelPath, $stub);
        $this->info('Model created successfully.');
    }

    protected function createController($model, $columns)
    {
        $controllerPath = app_path('Http/Controllers/'.$model.'Controller.php');
        $stub = File::get(__DIR__.'/../stubs/controller.stub');

        $validationRules = $this->generateValidationRules($columns, false);
        $validationUpdateRules = $this->generateValidationRules($columns, true);

        $stub = str_replace(
            [
                '{{modelName}}',
                '{{modelVariable}}',
                '{{validationRules}}',
                '{{validationUpdateRules}}'
            ],
            [
                $model,
                lcfirst($model),
                $validationRules,
                $validationUpdateRules
            ],
            $stub
        );

        File::put($controllerPath, $stub);
        $this->info('Controller created successfully.');
    }

    protected function generateValidationRules($columns, $isUpdate = false): string
    {
        $rules = [];
        foreach ($columns as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = $parts[1] ?? 'string';
            $required = isset($parts[2]) && $parts[2] === 'required';

            $rule = [];

            if ($isUpdate) {
                $rule[] = 'sometimes';
            }

            if ($required) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            switch ($type) {
                case 'string':
                    $rule[] = 'string';
                    $rule[] = 'max:255';
                    break;
                case 'integer':
                    $rule[] = 'integer';
                    break;
                case 'decimal':
                    $rule[] = 'numeric';
                    break;
                case 'boolean':
                    $rule[] = 'boolean';
                    break;
                case 'text':
                    $rule[] = 'string';
                    break;
                case 'date':
                    $rule[] = 'date';
                    break;
                case 'email':
                    $rule[] = 'email';
                    break;
            }

            $rules[] = "            '{$name}' => '" . implode('|', $rule) . "'";
        }

        return implode(",\n", $rules);
    }

    protected function generateDefaultValues($columns): string
    {
        $defaults = [];
        foreach ($columns as $column) {
            $parts = explode(':', $column);
            if (isset($parts[3])) {
                $name = $parts[0];
                $type = $parts[1];
                $default = $parts[3];

                if ($type === 'boolean') {
                    $default = filter_var($default, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                }
                elseif ($type === 'string' || $type === 'text') {
                    $default = "'$default'";
                }

                $defaults[] = "        '$name' => $default";
            }
        }

        return empty($defaults) ? '' : "\n    protected \$attributes = [\n" . implode(",\n", $defaults) . "\n    ];";
    }

    protected function generateMigrationFields($columns): string
    {
        $fields = ['$table->id();'];

        foreach ($columns as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = $parts[1] ?? 'string';
            $nullable = isset($parts[2]) && $parts[2] === 'nullable';
            $default = $parts[3] ?? null;

            $field = "\$table->{$type}('{$name}')";

            if ($nullable) {
                $field .= '->nullable()';
            } else {
                $field .= '->nullable(false)';
            }

            if ($default !== null) {
                if ($type === 'boolean') {
                    $defaultValue = filter_var($default, FILTER_VALIDATE_BOOLEAN);
                    $field .= "->default(" . ($defaultValue ? 'true' : 'false') . ")";
                } elseif ($type === 'integer' || $type === 'decimal') {
                    $field .= "->default($default)";
                } else {
                    $field .= "->default('$default')";
                }
            }

            if ($type === 'string' && $name === 'email') {
                $field .= '->unique()';
            }

            $fields[] = $field . ';';
        }

        $fields[] = '$table->timestamps();';

        return implode("\n            ", $fields);
    }

    protected function generateFillableFields($columns): string
    {
        $fillable = [];
        foreach ($columns as $column) {
            $parts = explode(':', $column);
            $fillable[] = "'" . $parts[0] . "'";
        }
        return implode(', ', $fillable);
    }
}
