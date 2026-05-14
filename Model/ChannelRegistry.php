<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model;

use Gaudit\AiCommerce\Api\ChannelInterface;

class ChannelRegistry
{
    /**
     * @param ChannelInterface[] $channels
     */
    public function __construct(private readonly array $channels = [])
    {
    }

    public function get(string $channelId): ?ChannelInterface
    {
        foreach ($this->channels as $channel) {
            if ($channel instanceof ChannelInterface && $channel->getChannelId() === $channelId) {
                return $channel;
            }
        }
        return null;
    }

    /**
     * @return ChannelInterface[]
     */
    public function getAll(?int $storeId = null): array
    {
        $enabled = [];
        foreach ($this->channels as $channel) {
            if ($channel instanceof ChannelInterface && $channel->isEnabled($storeId)) {
                $enabled[$channel->getChannelId()] = $channel;
            }
        }
        return $enabled;
    }
}
