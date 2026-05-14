<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Llm;

use Gaudit\AiCommerce\Api\LlmClientInterface;
use Gaudit\AiCommerce\Exception\LlmException;
use Gaudit\AiCommerce\Model\Config;

class LlmClientFactory
{
    /**
     * @param LlmClientInterface[] $clients keyed by provider id (anthropic, openai, gemini, ...)
     */
    public function __construct(
        private readonly Config $config,
        private readonly array $clients = []
    ) {
    }

    public function forStore(int $storeId): LlmClientInterface
    {
        $provider = $this->config->getLlmProvider($storeId);
        if (!isset($this->clients[$provider])) {
            throw new LlmException(__('Unknown LLM provider: %1', $provider));
        }
        return $this->clients[$provider];
    }

    public function getClient(string $provider): LlmClientInterface
    {
        if (!isset($this->clients[$provider])) {
            throw new LlmException(__('Unknown LLM provider: %1', $provider));
        }
        return $this->clients[$provider];
    }
}
