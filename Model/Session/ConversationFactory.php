<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Session;

class ConversationFactory
{
    public function fromRow(array $row): Conversation
    {
        return new Conversation(
            isset($row['entity_id']) ? (int)$row['entity_id'] : null,
            (string)($row['channel_id'] ?? ''),
            (string)($row['external_conversation_id'] ?? ''),
            (int)($row['store_id'] ?? 0),
            isset($row['customer_id']) ? (int)$row['customer_id'] : null,
            isset($row['quote_id']) ? (int)$row['quote_id'] : null,
            (string)($row['created_at'] ?? ''),
            (string)($row['updated_at'] ?? '')
        );
    }
}
