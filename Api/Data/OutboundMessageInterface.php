<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api\Data;

interface OutboundMessageInterface
{
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_PRODUCT_CARD = 'product_card';
    public const TYPE_BUTTONS = 'buttons';

    public function getChannelId(): string;

    public function getExternalConversationId(): string;

    public function getType(): string;

    public function getText(): string;

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array;
}
