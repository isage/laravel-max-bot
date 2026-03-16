<?php

namespace Blacky0892\Max\Services;

use Blacky0892\Max\MaxApiClient;

class SubscriptionService
{
    public function __construct(
        protected MaxApiClient $client,
    ) {
    }

    public function all(): array
    {
        return $this->client->get('/subscriptions');
    }

    public function create(
        string $url,
        array $updateTypes = [],
        ?string $secret = null,
        ?int $version = null,
    ): array {
        $body = array_filter([
            'url' => $url,
            'update_types' => $updateTypes ?: null,
            'secret' => $secret,
            'version' => $version,
        ], static fn ($value) => $value !== null);

        return $this->client->post('/subscriptions', $body);
    }

    public function delete(string $url): array
    {
        return $this->client->delete('/subscriptions', [], [
            'url' => $url,
        ]);
    }
}