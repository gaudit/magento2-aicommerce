<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Bridge;

use Gaudit\AiCommerce\Model\Config;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Bridge endpoint: POST /aicommerce/bridge
 *
 * Forwards the request to the co-running Node.js AI Commerce bot. Use during
 * migration; disable once PHP path reaches parity.
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly Curl $curl
    ) {
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $url = rtrim($this->config->getScalar('aicommerce/bridge/node_bot_url', $storeId), '/');

        if (!$url) {
            return $result->setHttpResponseCode(503)->setData(['error' => 'Bridge URL not configured']);
        }

        $this->curl->setHeaders(['content-type' => 'application/json']);
        $this->curl->setOption(CURLOPT_TIMEOUT, 30);
        $this->curl->post("{$url}/bot/chat", (string)$this->request->getContent());

        $body = (string)$this->curl->getBody();
        $status = (int)$this->curl->getStatus();
        $decoded = json_decode($body, true);
        return $result->setHttpResponseCode($status ?: 200)->setData(is_array($decoded) ? $decoded : ['raw' => $body]);
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
