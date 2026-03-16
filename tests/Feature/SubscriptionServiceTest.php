<?php

namespace Blacky0892\Max\Tests\Feature;

use Blacky0892\Max\Services\SubscriptionService;
use Blacky0892\Max\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class SubscriptionServiceTest extends TestCase
{
    public function test_it_can_get_all_subscriptions(): void
    {
        Http::fake([
            'https://platform-api.max.ru/subscriptions' => Http::response([
                ['url' => 'https://example.com/max/webhook'],
            ], 200),
        ]);

        $service = app(SubscriptionService::class);

        $response = $service->all();

        $this->assertCount(1, $response);
        $this->assertSame('https://example.com/max/webhook', $response[0]['url']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://platform-api.max.ru/subscriptions'
                && $request->method() === 'GET';
        });
    }

    public function test_it_can_create_subscription(): void
    {
        Http::fake([
            'https://platform-api.max.ru/subscriptions' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $service = app(SubscriptionService::class);

        $response = $service->create(
            url: 'https://example.com/max/webhook',
            updateTypes: ['message_created', 'message_callback', 'bot_started'],
            secret: 'test-secret',
            version: 1
        );

        $this->assertTrue($response['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://platform-api.max.ru/subscriptions'
                && $request->method() === 'POST'
                && ($data['url'] ?? null) === 'https://example.com/max/webhook'
                && ($data['secret'] ?? null) === 'test-secret'
                && ($data['version'] ?? null) === 1
                && ($data['update_types'] ?? []) === ['message_created', 'message_callback', 'bot_started'];
        });
    }

    public function test_it_can_delete_subscription(): void
    {
        Http::fake([
            'https://platform-api.max.ru/subscriptions?url=https%3A%2F%2Fexample.com%2Fmax%2Fwebhook' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $service = app(SubscriptionService::class);

        $response = $service->delete('https://example.com/max/webhook');

        $this->assertTrue($response['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://platform-api.max.ru/subscriptions?url=https%3A%2F%2Fexample.com%2Fmax%2Fwebhook'
                && $request->method() === 'DELETE';
        });
    }
}