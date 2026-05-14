<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Webhook;

class Telegram extends AbstractWebhook
{
    protected function getChannelId(): string
    {
        return 'telegram';
    }
}
