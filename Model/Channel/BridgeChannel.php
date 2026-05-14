<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Channel;

use Gaudit\AiCommerce\Api\Data\InboundMessageInterface;
use Gaudit\AiCommerce\Api\Data\OutboundMessageInterface;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\InboundMessage;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Bridge channel — proxies to a co-running Node.js AI Commerce bot.
 *
 * Useful during migration from the original Node bot to this PHP module:
 * enable per-channel toggling in admin, route the heavy lifting to whichever
 * backend is more complete. Disable once the PHP path reaches parity.
 */
class BridgeChannel extends AbstractChannel
{
    public function __construct(
        Config $config,
        private readonly Curl $curl
    ) {
        parent::__construct($config);
    }

    public function getChannelId(): string
    {
        return 'bridge';
    }

    public function verifyRequest(string $rawBody, array $headers): void
    {
        // Bridge is internal — trust network boundary.
    }

    public function parseInbound(string $rawBody, array $headers): ?InboundMessageInterface
    {
        $body = json_decode($rawBody, true);
        if (!is_array($body)) {
            return null;
        }
        return new InboundMessage(
            $this->getChannelId(),
            (string)($body['conversation_id'] ?? ''),
            (string)($body['user_id'] ?? ''),
            (string)($body['text'] ?? ''),
            [],
            $body
        );
    }

    public function send(OutboundMessageInterface $message): void
    {
        $url = rtrim($this->config->getScalar('aicommerce/bridge/node_bot_url'), '/');
        if (!$url) {
            return;
        }
        $this->curl->setHeaders(['content-type' => 'application/json']);
        $this->curl->post("{$url}/bot/chat", json_encode([
            'conversation_id' => $message->getExternalConversationId(),
            'text' => $message->getText(),
        ]));
    }
}
