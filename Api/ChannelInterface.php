<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api;

use Gaudit\AiCommerce\Api\Data\InboundMessageInterface;
use Gaudit\AiCommerce\Api\Data\OutboundMessageInterface;

/**
 * Contract for a chat channel (Telegram, WhatsApp, MCP, REST, ...).
 *
 * A Channel is responsible for:
 *  - parsing inbound webhook payloads into a normalized message
 *  - sending outbound messages back to the user in the channel's format
 *  - authenticating incoming requests (signature, token, etc)
 */
interface ChannelInterface
{
    public function getChannelId(): string;

    public function isEnabled(?int $storeId = null): bool;

    /**
     * Parse the raw webhook body into a normalized inbound message.
     * Return null if the event should be ignored (typing indicators, etc).
     */
    public function parseInbound(string $rawBody, array $headers): ?InboundMessageInterface;

    /**
     * Verify the request authenticity (HMAC, bearer, etc).
     * Throw \Gaudit\AiCommerce\Exception\AuthenticationException on failure.
     */
    public function verifyRequest(string $rawBody, array $headers): void;

    /**
     * Send an outbound message back to the user via the channel.
     */
    public function send(OutboundMessageInterface $message): void;
}
