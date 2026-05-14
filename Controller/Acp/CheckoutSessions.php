<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Acp;

use Gaudit\AiCommerce\Model\Config;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * ACP — Agentic Commerce Protocol (OpenAI + Stripe).
 *
 * Endpoint: POST /aicommerce/acp/v1/checkout_sessions
 *
 * Spec: https://github.com/openai/openai-agents/agentic-commerce-protocol
 *  (link valid as of spec drop announced with ChatGPT "Buy It" feature)
 *
 * STATUS: skeleton — surface returns valid empty session.
 * Full implementation should:
 *  1. Accept items[] + buyer + shipping_address
 *  2. Create a Magento quote (Magento\Quote\Api\GuestCartManagementInterface)
 *  3. Compute totals + shipping options
 *  4. Return CheckoutSession with payment_provider_data ready for confirmation
 *  5. Honor Idempotency-Key header
 */
class CheckoutSessions implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();

        if (!$this->config->isEnabled($storeId) || !$this->config->isChannelEnabled('acp', $storeId)) {
            return $result->setHttpResponseCode(404)->setData(['error' => 'ACP disabled']);
        }

        $body = json_decode((string)$this->request->getContent(), true) ?? [];

        return $result->setData([
            'id' => 'cs_' . bin2hex(random_bytes(12)),
            'status' => 'requires_action',
            'message' => 'ACP skeleton — full implementation pending. See Controller/Acp/CheckoutSessions.php',
            'echo' => $body,
        ]);
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
