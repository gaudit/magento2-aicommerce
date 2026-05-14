<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Controller\Webhook;

use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Meta Cloud webhook also serves the verification GET handshake.
 */
class Meta extends AbstractWebhook implements HttpGetActionInterface
{
    protected function getChannelId(): string
    {
        return 'meta_cloud';
    }

    public function execute()
    {
        if ($this->request->getMethod() === 'GET') {
            $verifyToken = $this->config->getChannelSecret('aicommerce/channels/meta_verify_token');
            $mode = (string)$this->request->getParam('hub_mode');
            $token = (string)$this->request->getParam('hub_verify_token');
            $challenge = (string)$this->request->getParam('hub_challenge');

            $result = $this->jsonFactory->create();
            if ($mode === 'subscribe' && $verifyToken && hash_equals($verifyToken, $token)) {
                return $result->setData($challenge);
            }
            return $result->setHttpResponseCode(403)->setData(['error' => 'verification failed']);
        }

        return parent::execute();
    }
}
