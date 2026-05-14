<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api\Data;

interface ConversationInterface
{
    public function getId(): ?int;

    public function getChannelId(): string;

    public function getExternalConversationId(): string;

    public function getStoreId(): int;

    public function getCustomerId(): ?int;

    public function getQuoteId(): ?int;

    public function getCreatedAt(): string;

    public function getUpdatedAt(): string;
}
