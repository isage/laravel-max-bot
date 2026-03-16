<?php

namespace Blacky0892\Max\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Blacky0892\Max\MaxApiClient client()
 * @method static \Blacky0892\Max\Services\SubscriptionService subscriptions()
 * @method static \Blacky0892\Max\Services\CallbackService callbacks()
 */
class Max extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'max';
    }
}