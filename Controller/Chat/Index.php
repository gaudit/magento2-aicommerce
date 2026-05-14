<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Chat;

use Gaudit\AiCommerce\Api\ConversationRepositoryInterface;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\Message;
use Gaudit\AiCommerce\Model\Orchestrator;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Generic chat endpoint: POST /aicommerce/chat
 *
 * Body: { "conversation_id": "string", "text": "string" }
 *
 * The thinnest possible front door — use for custom UI, MCP-incapable agents,
 * or quick curl tests. Conversation is persisted under channel "rest".
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly Orchestrator $orchestrator,
        private readonly ConversationRepositoryInterface $conversations,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();

        if (!$this->config->isEnabled($storeId)) {
            return $result->setHttpResponseCode(404)->setData(['error' => 'AI Commerce disabled']);
        }

        $body = json_decode((string)$this->request->getContent(), true);
        if (!is_array($body) || empty($body['text'])) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'text is required']);
        }

        $conversationId = (string)($body['conversation_id'] ?? bin2hex(random_bytes(8)));

        try {
            $conversation = $this->conversations->getOrCreate('rest', $conversationId, $storeId);
            $history = $this->conversations->getMessages((int)$conversation->getId(), 30);
            $this->conversations->appendMessage((int)$conversation->getId(), Message::user((string)$body['text']));

            $output = $this->orchestrator->run((string)$body['text'], $history, $storeId);
            $this->conversations->appendMessage((int)$conversation->getId(), Message::assistant($output['text']));

            return $result->setData([
                'conversation_id' => $conversationId,
                'text' => $output['text'],
                'usage' => $output['usage'],
                'turns' => $output['turns'],
            ]);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(500)->setData(['error' => $e->getMessage()]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
