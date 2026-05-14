<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api;

use Gaudit\AiCommerce\Api\Data\ConversationInterface;
use Gaudit\AiCommerce\Api\Data\MessageInterface;

interface ConversationRepositoryInterface
{
    public function getOrCreate(string $channelId, string $externalConversationId, int $storeId): ConversationInterface;

    public function appendMessage(int $conversationId, MessageInterface $message): void;

    /**
     * @return MessageInterface[]
     */
    public function getMessages(int $conversationId, int $limit = 50): array;
}
