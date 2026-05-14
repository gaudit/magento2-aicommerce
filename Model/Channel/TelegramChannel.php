<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Channel;

use Gaudit\AiCommerce\Api\Data\InboundMessageInterface;
use Gaudit\AiCommerce\Api\Data\OutboundMessageInterface;
use Gaudit\AiCommerce\Exception\AuthenticationException;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\InboundMessage;
use Magento\Framework\HTTP\Client\Curl;

class TelegramChannel extends AbstractChannel
{
    private const API_BASE = 'https://api.telegram.org/bot';

    public function __construct(
        Config $config,
        private readonly Curl $curl
    ) {
        parent::__construct($config);
    }

    public function getChannelId(): string
    {
        return 'telegram';
    }

    public function verifyRequest(string $rawBody, array $headers): void
    {
        // Telegram authenticates by URL-embedded secret token in the webhook path
        // OR via the X-Telegram-Bot-Api-Secret-Token header. Validate at controller layer.
        $expectedSecret = $this->config->getChannelSecret('aicommerce/channels/telegram_webhook_secret');
        $provided = $headers['X-Telegram-Bot-Api-Secret-Token'] ?? $headers['x-telegram-bot-api-secret-token'] ?? '';
        if ($expectedSecret && !hash_equals($expectedSecret, (string)$provided)) {
            throw new AuthenticationException(__('Invalid Telegram webhook secret.'));
        }
    }

    public function parseInbound(string $rawBody, array $headers): ?InboundMessageInterface
    {
        $update = json_decode($rawBody, true);
        if (!is_array($update)) {
            return null;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!$message || empty($message['text'])) {
            return null;
        }

        $chatId = (string)$message['chat']['id'];
        $userId = (string)($message['from']['id'] ?? $chatId);

        return new InboundMessage(
            $this->getChannelId(),
            $chatId,
            $userId,
            (string)$message['text'],
            [],
            $update
        );
    }

    public function send(OutboundMessageInterface $message): void
    {
        $token = $this->config->getChannelSecret('aicommerce/channels/telegram_bot_token');
        if (!$token) {
            return;
        }

        $payload = [
            'chat_id' => $message->getExternalConversationId(),
            'text' => $message->getText(),
            'parse_mode' => 'Markdown',
        ];

        $this->curl->setHeaders(['content-type' => 'application/json']);
        $this->curl->post(self::API_BASE . $token . '/sendMessage', json_encode($payload));
    }
}
