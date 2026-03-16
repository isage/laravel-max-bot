<?php

namespace Blacky0892\Max\Tests\Feature;

use Blacky0892\Max\Tests\Fixtures\TestWebhookHandler;
use Blacky0892\Max\Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestWebhookHandler::reset();
    }

    public function test_webhook_route_accepts_valid_secret_and_dispatches_handler(): void
    {
        $payload = [
            'update_type' => 'message_created',
            'message' => [
                'chat_id' => 100,
                'text' => 'Привет',
            ],
        ];

        $response = $this
            ->withHeaders([
                'X-Max-Bot-Api-Secret' => 'test-secret',
            ])
            ->postJson('/max/webhook', $payload);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertSame($payload, TestWebhookHandler::$handledPayload);
    }

    public function test_webhook_route_rejects_invalid_secret(): void
    {
        $response = $this
            ->withHeaders([
                'X-Max-Bot-Api-Secret' => 'wrong-secret',
            ])
            ->postJson('/max/webhook', [
                'update_type' => 'message_created',
            ]);

        $response->assertForbidden();
    }

    public function test_webhook_route_accepts_request_when_secret_is_disabled(): void
    {
        config()->set('max.webhook_secret', null);

        $payload = [
            'update_type' => 'message_created',
            'message' => [
                'chat_id' => 200,
            ],
        ];

        $response = $this->postJson('/max/webhook', $payload);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertSame($payload, TestWebhookHandler::$handledPayload);
    }
}