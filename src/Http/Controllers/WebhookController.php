<?php

namespace Blacky0892\Max\Http\Controllers;

use Blacky0892\Max\Contracts\HandlesMaxUpdates;
use Blacky0892\Max\Services\CallbackService;
use Blacky0892\Max\Support\Update;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        CallbackService $callbacks,
    ): JsonResponse {
        $callbacks->ensureValidWebhookSecret($request);
        
        $payload = $request->all();
        
        $update = Update::make($payload);

        $handlerClass = config('max.webhook.handler');

        if ($handlerClass) {
            $handler = App::make($handlerClass);

            if (! $handler instanceof HandlesMaxUpdates) {
                throw new InvalidArgumentException(sprintf(
                    'MAX webhook handler [%s] must implement [%s].',
                    $handlerClass,
                    HandlesMaxUpdates::class
                ));
            }

            $handler->handle($update);
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}