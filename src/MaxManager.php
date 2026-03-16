<?php

namespace Blacky0892\Max;

use Blacky0892\Max\Services\CallbackService;
use Blacky0892\Max\Services\SubscriptionService;

class MaxManager
{
    public function __construct(
        protected MaxApiClient $client,
        protected SubscriptionService $subscriptions,
        protected CallbackService $callbacks,
    ) {
    }

    public function client(): MaxApiClient
    {
        return $this->client;
    }

    public function subscriptions(): SubscriptionService
    {
        return $this->subscriptions;
    }

    public function callbacks(): CallbackService
    {
        return $this->callbacks;
    }
}