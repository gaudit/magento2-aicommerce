<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Webhook;

use Gaudit\AiCommerce\Api\ConversationRepositoryInterface;
use Gaudit\AiCommerce\Exception\AuthenticationException;
use Gaudit\AiCommerce\Model\ChannelRegistry;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\Message;
use Gaudit\AiCommerce\Model\Data\OutboundMessage;
use Gaudit\AiCommerce\Model\Orchestrator;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractWebhook implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        protected readonly RequestInterface $request,
        protected readonly JsonFactory $jsonFactory,
        protected readonly ChannelRegistry $channels,
        protected readonly Orchestrator $orchestrator,
        protected readonly ConversationRepositoryInterface $conversations,
        protected readonly Config $config,
        protected readonly StoreManagerInterface $storeManager,
        protected readonly LoggerInterface $logger
    ) {
    }

    abstract protected function getChannelId(): string;

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $channelId = $this->getChannelId();

        if (!$this->config->isEnabled($storeId) || !$this->config->isChannelEnabled($channelId, $storeId)) {
            return $result->setHttpResponseCode(404)->setData(['error' => 'channel disabled']);
        }

        $channel = $this->channels->get($channelId);
        if (!$channel) {
            return $result->setHttpResponseCode(404)->setData(['error' => 'channel not registered']);
        }

        $body = (string)$this->request->getContent();
        $headers = $this->collectHeaders();

        try {
            $channel->verifyRequest($body, $headers);
        } catch (AuthenticationException $e) {
            $this->logger->warning('[aicommerce] webhook auth failed', [
                'channel' => $channelId, 'error' => $e->getMessage(),
            ]);
            return $result->setHttpResponseCode(401)->setData(['error' => 'unauthorized']);
        }

        $inbound = $channel->parseInbound($body, $headers);
        if (!$inbound) {
            return $result->setData(['ok' => true, 'skipped' => true]);
        }

        try {
            $conversation = $this->conversations->getOrCreate(
                $channelId,
                $inbound->getExternalConversationId(),
                $storeId
            );

            $history = $this->conversations->getMessages((int)$conversation->getId(), 30);
            $this->conversations->appendMessage((int)$conversation->getId(), Message::user($inbound->getText()));

            $output = $this->orchestrator->run($inbound->getText(), $history, $storeId);

            $this->conversations->appendMessage((int)$conversation->getId(), Message::assistant($output['text']));

            $channel->send(OutboundMessage::text(
                $channelId,
                $inbound->getExternalConversationId(),
                $output['text']
            ));
        } catch (\Throwable $e) {
            $this->logger->error('[aicommerce] webhook processing failed', [
                'channel' => $channelId, 'error' => $e->getMessage(),
            ]);
            return $result->setHttpResponseCode(500)->setData(['error' => 'internal']);
        }

        return $result->setData(['ok' => true]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    private function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string)$value;
                $headers[strtolower($name)] = (string)$value;
            }
        }
        return $headers;
    }
}
