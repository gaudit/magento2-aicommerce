<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Mcp;

use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Protocol\Mcp\Server;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * MCP endpoint: POST/GET /aicommerce/mcp
 *
 * - GET returns a discovery page (capabilities) for browser checks.
 * - POST accepts JSON-RPC 2.0 requests (initialize, tools/list, tools/call, ping).
 *
 * Authentication is delegated to a shared secret in the `Authorization: Bearer ...`
 * header (set in admin). MCP clients support custom headers via their config.
 */
class Index implements HttpPostActionInterface, HttpGetActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly Server $server,
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

        if (!$this->config->isChannelEnabled('mcp', $storeId)) {
            return $result->setHttpResponseCode(404)->setData(['error' => 'MCP endpoint disabled']);
        }

        if ($this->request->getMethod() === 'GET') {
            return $result->setData([
                'name' => 'Gaudit AiCommerce MCP Server',
                'version' => '0.1.0',
                'protocol' => '2025-03-26',
                'transport' => 'streamable-http',
                'usage' => 'POST JSON-RPC 2.0 here. Methods: initialize, tools/list, tools/call, ping.',
            ]);
        }

        $body = (string)$this->request->getContent();
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return $result->setHttpResponseCode(400)->setData([
                'jsonrpc' => '2.0',
                'error' => ['code' => -32700, 'message' => 'Parse error'],
            ]);
        }

        // Support both single requests and batch
        if (array_is_list($decoded) && !empty($decoded)) {
            $responses = [];
            foreach ($decoded as $req) {
                $resp = $this->server->handle((array)$req, $storeId);
                if ($resp !== null) {
                    $responses[] = $resp;
                }
            }
            return $result->setData($responses);
        }

        $response = $this->server->handle($decoded, $storeId);
        if ($response === null) {
            return $result->setHttpResponseCode(204);
        }
        return $result->setData($response);
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
