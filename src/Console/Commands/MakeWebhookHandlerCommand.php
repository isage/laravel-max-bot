<?php
declare(strict_types=1);


namespace Blacky0892\Max\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeWebhookHandlerCommand extends Command
{
    protected $signature = 'make:max-webhook-handler
                            {name=MaxWebhookHandler : Class name, for example MaxWebhookHandler or Services/Max/MaxWebhookHandler}
                            {--force : Overwrite the file if it already exists}';

    protected $description = 'Create a MAX webhook handler class';

    public function handle(Filesystem $files): int
    {
        $name = str_replace('\\', '/', (string)$this->argument('name'));

        if (!str_contains($name, '/')) {
            $name = 'Services/Max/' . $name;
        }

        $class = Str::afterLast($name, '/');
        $relativePath = app_path($name . '.php');
        $namespaceSuffix = str_replace('/', '\\', Str::beforeLast($name, '/'));
        $namespace = 'App' . ($namespaceSuffix ? '\\' . $namespaceSuffix : '');

        if ($files->exists($relativePath) && !$this->option('force')) {
            $this->error("File already exists: {$relativePath}");

            return self::FAILURE;
        }

        $stubPath = __DIR__ . '/../../../stubs/MaxWebhookHandler.php.stub';

        if (!$files->exists($stubPath)) {
            $this->error("Stub not found: {$stubPath}");

            return self::FAILURE;
        }

        $stub = $files->get($stubPath);

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $class],
            $stub
        );

        $files->ensureDirectoryExists(dirname($relativePath));
        $files->put($relativePath, $content);

        $this->info("MAX webhook handler created: {$relativePath}");
        $this->line("Set MAX_WEBHOOK_HANDLER={$namespace}\\{$class} in your .env");

        return self::SUCCESS;
    }
}