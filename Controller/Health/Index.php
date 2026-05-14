<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Health;

use Gaudit\AiCommerce\Api\ToolRegistryInterface;
use Gaudit\AiCommerce\Model\ChannelRegistry;
use Gaudit\AiCommerce\Model\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Public health endpoint: GET /aicommerce/health
 *
 * Returns a JSON snapshot of module status. Designed for uptime monitors
 * and quick "is the module installed?" checks from the audience at demo time.
 *
 * Intentionally exposes no secrets — only booleans and counts.
 */
class Index implements HttpGetActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly Config $config,
        private readonly ToolRegistryInterface $tools,
        private readonly ChannelRegistry $channels,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function execute()
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $tools = $this->tools->getAll($storeId);
        $channels = $this->channels->getAll($storeId);

        $enabledChannels = [];
        foreach ($channels as $channel) {
            $enabledChannels[] = $channel->getChannelId();
        }

        return $this->jsonFactory->create()->setData([
            'name' => 'Gaudit_AiCommerce',
            'version' => '0.1.0',
            'status' => $this->config->isEnabled($storeId) ? 'enabled' : 'disabled',
            'store_id' => $storeId,
            'llm' => [
                'provider' => $this->config->getLlmProvider($storeId),
                'model' => $this->config->getLlmModel($storeId),
                'api_key_configured' => $this->config->getLlmApiKey($storeId) !== '',
            ],
            'tools' => [
                'count' => count($tools),
                'names' => array_keys($tools),
            ],
            'channels' => [
                'enabled' => $enabledChannels,
            ],
            'protocols' => [
                'mcp' => $this->config->isChannelEnabled('mcp', $storeId),
                'acp' => $this->config->isChannelEnabled('acp', $storeId),
                'ucp' => $this->config->isChannelEnabled('ucp', $storeId),
            ],
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
