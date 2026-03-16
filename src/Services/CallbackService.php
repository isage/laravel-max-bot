<?php

namespace Blacky0892\Max\Services;

use Blacky0892\Max\MaxApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CallbackService
{
    public function __construct(
        protected MaxApiClient $client,
    ) {
    }

    public function answer(
        string $callbackId,
        ?string $notification = null,
        ?array $message = null,
    ): array {
        return $this->client->answerCallback(
            callbackId: $callbackId,
            notification: $notification,
            message: $message,
        );
    }

    public function validateWebhookSecret(Request $request): bool
    {
        $expectedSecret = config('max.webhook_secret');

        if (blank($expectedSecret)) {
            return true;
        }

        $actualSecret = $request->header('X-Max-Bot-Api-Secret');

        return hash_equals((string) $expectedSecret, (string) $actualSecret);
    }

    public function ensureValidWebhookSecret(Request $request): void
    {
        if (! $this->validateWebhookSecret($request)) {
            throw new AccessDeniedHttpException('Invalid MAX webhook secret.');
        }
    }

    public function callbackIdFromUpdate(array $update): ?string
    {
        return data_get($update, 'callback.id')
            ?? data_get($update, 'message_callback.callback_id')
            ?? data_get($update, 'payload.callback_id');
    }
}