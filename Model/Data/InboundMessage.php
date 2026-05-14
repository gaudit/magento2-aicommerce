<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Data;

use Gaudit\AiCommerce\Api\Data\InboundMessageInterface;

class InboundMessage implements InboundMessageInterface
{
    public function __construct(
        private readonly string $channelId,
        private readonly string $externalConversationId,
        private readonly string $externalUserId,
        private readonly string $text,
        private readonly array $attachments = [],
        private readonly array $rawPayload = []
    ) {
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getExternalConversationId(): string
    {
        return $this->externalConversationId;
    }

    public function getExternalUserId(): string
    {
        return $this->externalUserId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getRawPayload(): array
    {
        return $this->rawPayload;
    }
}
