<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Channel;

use Gaudit\AiCommerce\Api\Data\InboundMessageInterface;
use Gaudit\AiCommerce\Api\Data\OutboundMessageInterface;
use Gaudit\AiCommerce\Exception\AuthenticationException;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\InboundMessage;
use Magento\Framework\HTTP\Client\Curl;

/**
 * WhatsApp Cloud API (official Meta) channel.
 *
 * Spec: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * Webhook signature verified via X-Hub-Signature-256 (HMAC-SHA256 over the raw body
 * using the App Secret). Verify-token used for the GET handshake at controller layer.
 */
class MetaCloudChannel extends AbstractChannel
{
    private const GRAPH_BASE = 'https://graph.facebook.com/v19.0';

    public function __construct(
        Config $config,
        private readonly Curl $curl
    ) {
        parent::__construct($config);
    }

    public function getChannelId(): string
    {
        return 'meta_cloud';
    }

    public function verifyRequest(string $rawBody, array $headers): void
    {
        $appSecret = $this->config->getChannelSecret('aicommerce/channels/meta_app_secret');
        if (!$appSecret) {
            return;
        }
        $signature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? '';
        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        if (!hash_equals($expected, (string)$signature)) {
            throw new AuthenticationException(__('Invalid Meta webhook signature.'));
        }
    }

    public function parseInbound(string $rawBody, array $headers): ?InboundMessageInterface
    {
        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            return null;
        }

        $value = $event['entry'][0]['changes'][0]['value'] ?? [];
        $message = $value['messages'][0] ?? null;
        if (!$message || ($message['type'] ?? '') !== 'text') {
            return null;
        }

        $from = (string)($message['from'] ?? '');
        $text = (string)($message['text']['body'] ?? '');
        if ($from === '' || $text === '') {
            return null;
        }

        return new InboundMessage($this->getChannelId(), $from, $from, $text, [], $event);
    }

    public function send(OutboundMessageInterface $message): void
    {
        $phoneId = $this->config->getScalar('aicommerce/channels/meta_phone_id');
        $token = $this->config->getChannelSecret('aicommerce/channels/meta_access_token');
        if (!$phoneId || !$token) {
            return;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $message->getExternalConversationId(),
            'type' => 'text',
            'text' => ['body' => $message->getText()],
        ];

        $this->curl->setHeaders([
            'authorization' => 'Bearer ' . $token,
            'content-type' => 'application/json',
        ]);
        $this->curl->post(self::GRAPH_BASE . "/{$phoneId}/messages", json_encode($payload));
    }
}
