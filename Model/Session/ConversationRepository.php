<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Session;

use Gaudit\AiCommerce\Api\ConversationRepositoryInterface;
use Gaudit\AiCommerce\Api\Data\ConversationInterface;
use Gaudit\AiCommerce\Api\Data\MessageInterface;
use Gaudit\AiCommerce\Model\Data\Message;
use Magento\Framework\App\ResourceConnection;

/**
 * Minimal repository over gaudit_aicommerce_conversation/_message tables.
 *
 * Kept intentionally raw-SQL to avoid the model/resource boilerplate explosion
 * for v0.1. Will move to ResourceModel/Collection if/when admin grid lands.
 */
class ConversationRepository implements ConversationRepositoryInterface
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ConversationFactory $conversationFactory
    ) {
    }

    public function getOrCreate(string $channelId, string $externalConversationId, int $storeId): ConversationInterface
    {
        $conn = $this->resource->getConnection();
        $table = $this->resource->getTableName('gaudit_aicommerce_conversation');

        $row = $conn->fetchRow(
            $conn->select()->from($table)
                ->where('channel_id = ?', $channelId)
                ->where('external_conversation_id = ?', $externalConversationId)
                ->limit(1)
        );

        if (!$row) {
            $conn->insert($table, [
                'channel_id' => $channelId,
                'external_conversation_id' => $externalConversationId,
                'store_id' => $storeId,
            ]);
            $row = $conn->fetchRow(
                $conn->select()->from($table)->where('entity_id = ?', $conn->lastInsertId($table))
            );
        }

        return $this->conversationFactory->fromRow($row);
    }

    public function appendMessage(int $conversationId, MessageInterface $message): void
    {
        $conn = $this->resource->getConnection();
        $conn->insert($this->resource->getTableName('gaudit_aicommerce_message'), [
            'conversation_id' => $conversationId,
            'role' => $message->getRole(),
            'content' => $message->getContent(),
            'tool_calls_json' => $message->getToolCalls() ? json_encode($message->getToolCalls()) : null,
            'tool_results_json' => $message->getToolResults() ? json_encode($message->getToolResults()) : null,
        ]);
    }

    public function getMessages(int $conversationId, int $limit = 50): array
    {
        $conn = $this->resource->getConnection();
        $rows = $conn->fetchAll(
            $conn->select()->from($this->resource->getTableName('gaudit_aicommerce_message'))
                ->where('conversation_id = ?', $conversationId)
                ->order('entity_id ASC')
                ->limit($limit)
        );
        $messages = [];
        foreach ($rows as $row) {
            $messages[] = new Message(
                (string)$row['role'],
                (string)($row['content'] ?? ''),
                $row['tool_calls_json'] ? (array)json_decode((string)$row['tool_calls_json'], true) : [],
                $row['tool_results_json'] ? (array)json_decode((string)$row['tool_results_json'], true) : []
            );
        }
        return $messages;
    }
}
