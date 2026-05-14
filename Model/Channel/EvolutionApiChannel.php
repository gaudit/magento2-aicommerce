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
 * Evolution API channel — open-source WhatsApp gateway.
 *
 * Supports both Baileys (unofficial) and WhatsApp Cloud API (official) backends
 * transparently via the Evolution server config.
 *
 * Repo: https://github.com/EvolutionAPI/evolution-api
 */
class EvolutionApiChannel extends AbstractChannel
{
    public function __construct(
        Config $config,
        private readonly Curl $curl
    ) {
        parent::__construct($config);
    }

    public function getChannelId(): string
    {
        return 'evolution';
    }

    public function verifyRequest(string $rawBody, array $headers): void
    {
        $expected = $this->config->getChannelSecret('aicommerce/channels/evolution_api_key');
        $provided = $headers['apikey'] ?? $headers['Apikey'] ?? '';
        if ($expected && !hash_equals($expected, (string)$provided)) {
            throw new AuthenticationException(__('Invalid Evolution API key.'));
        }
    }

    public function parseInbound(string $rawBody, array $headers): ?InboundMessageInterface
    {
        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            return null;
        }

        // Evolution emits event=messages.upsert with the message in data.message
        $type = (string)($event['event'] ?? '');
        if ($type !== 'messages.upsert') {
            return null;
        }

        $data = $event['data'] ?? [];
        // Ignore messages sent by us
        if (!empty($data['key']['fromMe'])) {
            return null;
        }

        $text = $data['message']['conversation']
            ?? $data['message']['extendedTextMessage']['text']
            ?? '';
        if ($text === '') {
            return null;
        }

        $remoteJid = (string)($data['key']['remoteJid'] ?? '');
        return new InboundMessage(
            $this->getChannelId(),
            $remoteJid,
            $remoteJid,
            (string)$text,
            [],
            $event
        );
    }

    public function send(OutboundMessageInterface $message): void
    {
        $baseUrl = rtrim($this->config->getScalar('aicommerce/evolution/base_url'), '/');
        $instance = $this->config->getScalar('aicommerce/channels/evolution_instance');
        $apiKey = $this->config->getChannelSecret('aicommerce/channels/evolution_api_key');
        if (!$baseUrl || !$instance || !$apiKey) {
            return;
        }

        $payload = [
            'number' => $message->getExternalConversationId(),
            'text' => $message->getText(),
        ];

        $this->curl->setHeaders([
            'apikey' => $apiKey,
            'content-type' => 'application/json',
        ]);
        $this->curl->post("{$baseUrl}/message/sendText/{$instance}", json_encode($payload));
    }
}
