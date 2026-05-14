<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Channel;

use Gaudit\AiCommerce\Api\ChannelInterface;
use Gaudit\AiCommerce\Model\Config;

abstract class AbstractChannel implements ChannelInterface
{
    public function __construct(protected readonly Config $config)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->config->isChannelEnabled($this->getChannelId(), $storeId);
    }
}
