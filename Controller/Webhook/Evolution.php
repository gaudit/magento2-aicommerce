<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Webhook;

class Evolution extends AbstractWebhook
{
    protected function getChannelId(): string
    {
        return 'evolution';
    }
}
