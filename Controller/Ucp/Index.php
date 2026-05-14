<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Ucp;

use Gaudit\AiCommerce\Model\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * UCP — Universal Commerce Protocol (Google / Gemini).
 *
 * Endpoint: /aicommerce/ucp
 *
 * Spec: https://developers.google.com/merchant/ucp/guides
 *  Announced at NRF Big Show January 2026 — Gemini-driven commerce surface.
 *
 * STATUS: discovery stub. The spec defines product feed exposure + agentic
 * action endpoints. Roadmap:
 *  1. Implement product catalog feed in the UCP-prescribed schema
 *  2. Implement agentic endpoints for cart manipulation and checkout
 *  3. Wire authentication per Google's UCP auth model
 *  4. Add to Channel toggles in admin once feature-complete
 */
class Index implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
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

        if (!$this->config->isEnabled($storeId) || !$this->config->isChannelEnabled('ucp', $storeId)) {
            return $result->setHttpResponseCode(404)->setData(['error' => 'UCP disabled']);
        }

        return $result->setData([
            'protocol' => 'ucp',
            'version' => '0.0.0-skeleton',
            'reference' => 'https://developers.google.com/merchant/ucp/guides',
            'status' => 'Skeleton awaiting full spec implementation. Contributions welcome.',
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
