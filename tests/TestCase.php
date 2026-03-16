<?php

namespace Blacky0892\Max\Tests;

use Blacky0892\Max\MaxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MaxServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('max.token', 'test-token');
        $app['config']->set('max.base_url', 'https://platform-api.max.ru');
        $app['config']->set('max.timeout', 30);
        $app['config']->set('max.connect_timeout', 10);
        $app['config']->set('max.retry_times', 0);
        $app['config']->set('max.retry_sleep', 200);
        $app['config']->set('max.webhook_secret', 'test-secret');
        $app['config']->set('max.route.enabled', true);
        $app['config']->set('max.route.path', '/max/webhook');
        $app['config']->set('max.route.middleware', ['api']);
        $app['config']->set('max.webhook.handler', \Blacky0892\Max\Tests\Fixtures\TestWebhookHandler::class);
    }
}