<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Session;

use Gaudit\AiCommerce\Api\Data\ConversationInterface;

class Conversation implements ConversationInterface
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $channelId,
        private readonly string $externalConversationId,
        private readonly int $storeId,
        private readonly ?int $customerId,
        private readonly ?int $quoteId,
        private readonly string $createdAt,
        private readonly string $updatedAt
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getExternalConversationId(): string
    {
        return $this->externalConversationId;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function getQuoteId(): ?int
    {
        return $this->quoteId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}
