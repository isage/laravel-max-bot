<?php

namespace Blacky0892\Max\Tests\Unit;

use Blacky0892\Max\MaxApiClient;
use Blacky0892\Max\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class MaxApiClientTest extends TestCase
{
    public function test_it_can_get_me(): void
    {
        Http::fake([
            'https://platform-api.max.ru/me' => Http::response([
                'user_id' => 123,
                'first_name' => 'Test Bot',
            ], 200),
        ]);

        $client = app(MaxApiClient::class);

        $response = $client->getMe();

        $this->assertSame(123, $response['user_id']);
        $this->assertSame('Test Bot', $response['first_name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://platform-api.max.ru/me'
                && $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    public function test_it_can_send_message_to_user(): void
    {
        Http::fake([
            'https://platform-api.max.ru/messages?user_id=555' => Http::response([
                'message' => ['mid' => 'm1'],
            ], 200),
        ]);

        $client = app(MaxApiClient::class);

        $response = $client->sendMessageToUser(
            userId: 555,
            text: 'Привет',
            format: 'markdown'
        );

        $this->assertSame('m1', $response['message']['mid']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://platform-api.max.ru/messages?user_id=555'
                && $request->method() === 'POST'
                && ($data['text'] ?? null) === 'Привет'
                && ($data['format'] ?? null) === 'markdown';
        });
    }

    public function test_it_can_edit_message(): void
    {
        Http::fake([
            'https://platform-api.max.ru/messages?message_id=777' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $client = app(MaxApiClient::class);

        $response = $client->editMessage(
            messageId: 777,
            text: 'Обновлено',
            format: 'html'
        );

        $this->assertTrue($response['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://platform-api.max.ru/messages?message_id=777'
                && $request->method() === 'PUT'
                && ($data['text'] ?? null) === 'Обновлено'
                && ($data['format'] ?? null) === 'html';
        });
    }

    public function test_it_can_delete_message(): void
    {
        Http::fake([
            'https://platform-api.max.ru/messages?message_id=777' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $client = app(MaxApiClient::class);

        $response = $client->deleteMessage(777);

        $this->assertTrue($response['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://platform-api.max.ru/messages?message_id=777'
                && $request->method() === 'DELETE';
        });
    }

    public function test_it_can_answer_callback(): void
    {
        Http::fake([
            'https://platform-api.max.ru/answers' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $client = app(MaxApiClient::class);

        $response = $client->answerCallback(
            callbackId: 'cb-1',
            notification: 'Готово'
        );

        $this->assertTrue($response['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://platform-api.max.ru/answers'
                && $request->method() === 'POST'
                && ($data['callback_id'] ?? null) === 'cb-1'
                && ($data['notification'] ?? null) === 'Готово';
        });
    }
}