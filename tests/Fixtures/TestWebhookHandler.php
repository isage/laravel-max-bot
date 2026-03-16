<?php

namespace Blacky0892\Max\Tests\Fixtures;

use Blacky0892\Max\Contracts\HandlesMaxUpdates;
use Blacky0892\Max\Support\Update;

class TestWebhookHandler implements HandlesMaxUpdates
{
    public static ?array $handledPayload = null;

    public function handle(Update $update): void
    {
        static::$handledPayload = $update->raw();
    }

    public static function reset(): void
    {
        static::$handledPayload = null;
    }
}