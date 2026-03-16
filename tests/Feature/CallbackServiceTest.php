<?php

namespace Blacky0892\Max\Tests\Feature;

use Blacky0892\Max\Services\CallbackService;
use Blacky0892\Max\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CallbackServiceTest extends TestCase
{
    public function test_it_validates_webhook_secret_successfully(): void
    {
        $service = app(CallbackService::class);

        $request = Request::create('/max/webhook', 'POST', [], [], [], [
            'HTTP_X_MAX_BOT_API_SECRET' => 'test-secret',
        ]);

        $this->assertTrue($service->validateWebhookSecret($request));
    }

    public function test_it_rejects_invalid_webhook_secret(): void
    {
        $service = app(CallbackService::class);

        $request = Request::create('/max/webhook', 'POST', [], [], [], [
            'HTTP_X_MAX_BOT_API_SECRET' => 'wrong-secret',
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        $service->ensureValidWebhookSecret($request);
    }

    public function test_it_can_answer_callback(): void
    {
        Http::fake([
            'https://platform-api.max.ru/answers' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $service = app(CallbackService::class);

        $response = $service->answer(
            callbackId: 'cb-42',
            notification: 'Обработано'
        );

        $this->assertTrue($response['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://platform-api.max.ru/answers'
                && $request->method() === 'POST'
                && ($data['callback_id'] ?? null) === 'cb-42'
                && ($data['notification'] ?? null) === 'Обработано';
        });
    }

    public function test_it_extracts_callback_id_from_update(): void
    {
        $service = app(CallbackService::class);

        $callbackId = $service->callbackIdFromUpdate([
            'message_callback' => [
                'callback_id' => 'cb-777',
            ],
        ]);

        $this->assertSame('cb-777', $callbackId);
    }
}