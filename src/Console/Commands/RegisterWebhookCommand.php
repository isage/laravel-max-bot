<?php

namespace Blacky0892\Max\Console\Commands;

use Blacky0892\Max\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegisterWebhookCommand extends Command
{
    protected $signature = 'max:webhook-register
                            {--url= : Full webhook URL. If omitted, APP_URL + MAX_ROUTE_PATH will be used}
                            {--types=message_created,message_callback,bot_started : Comma-separated update types}
                            {--secret= : Webhook secret. If omitted, MAX_WEBHOOK_SECRET will be used}
                            {--dry-run : Show payload without sending request}';

    protected $description = 'Register MAX webhook subscription';

    public function handle(SubscriptionService $subscriptions): int
    {
        $url = $this->option('url') ?: $this->buildWebhookUrl();
        $types = $this->parseTypes((string) $this->option('types'));
        $secret = $this->option('secret') ?: config('max.webhook_secret');

        if (blank($url)) {
            $this->error('Webhook URL is empty. Set APP_URL or pass --url=');

            return self::FAILURE;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("Invalid webhook URL: {$url}");

            return self::FAILURE;
        }

        $payload = [
            'url' => $url,
            'update_types' => $types,
            'secret' => $secret,
        ];

        $this->line('MAX webhook registration payload:');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. Request was not sent.');

            return self::SUCCESS;
        }

        $response = $subscriptions->create(
            url: $url,
            updateTypes: $types,
            secret: $secret,
        );

        $this->info('Webhook registered successfully.');
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    protected function buildWebhookUrl(): ?string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $path = (string) config('max.route.path', '/max/webhook');

        if (blank($appUrl)) {
            return null;
        }

        if (! Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }

        return $appUrl . $path;
    }

    protected function parseTypes(string $types): array
    {
        return collect(explode(',', $types))
            ->map(fn (string $type) => trim($type))
            ->filter()
            ->values()
            ->all();
    }
}