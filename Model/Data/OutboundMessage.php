<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Data;

use Gaudit\AiCommerce\Api\Data\OutboundMessageInterface;

class OutboundMessage implements OutboundMessageInterface
{
    public function __construct(
        private readonly string $channelId,
        private readonly string $externalConversationId,
        private readonly string $type,
        private readonly string $text,
        private readonly array $payload = []
    ) {
    }

    public static function text(string $channelId, string $externalConversationId, string $text): self
    {
        return new self($channelId, $externalConversationId, OutboundMessageInterface::TYPE_TEXT, $text);
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getExternalConversationId(): string
    {
        return $this->externalConversationId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
