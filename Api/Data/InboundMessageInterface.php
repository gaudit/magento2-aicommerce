<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api\Data;

interface InboundMessageInterface
{
    public function getChannelId(): string;

    public function getExternalConversationId(): string;

    public function getExternalUserId(): string;

    public function getText(): string;

    /**
     * @return array<int, array{type: string, url: string, mime_type?: string}>
     */
    public function getAttachments(): array;

    public function getRawPayload(): array;
}
