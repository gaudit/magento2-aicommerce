<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed accessor over Magento config. All system.xml fields surface here.
 */
class Config
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag('aicommerce/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSystemPrompt(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue('aicommerce/general/system_prompt', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getMonthlyBudgetUsd(?int $storeId = null): float
    {
        return (float)$this->scopeConfig->getValue('aicommerce/general/monthly_budget_usd', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getLlmProvider(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue('aicommerce/llm/provider', ScopeInterface::SCOPE_STORE, $storeId) ?: 'anthropic';
    }

    public function getLlmModel(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue('aicommerce/llm/model', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getLlmApiKey(?int $storeId = null): string
    {
        $encrypted = (string)$this->scopeConfig->getValue('aicommerce/llm/api_key', ScopeInterface::SCOPE_STORE, $storeId);
        return $encrypted ? $this->encryptor->decrypt($encrypted) : '';
    }

    public function getLlmMaxTokens(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue('aicommerce/llm/max_tokens', ScopeInterface::SCOPE_STORE, $storeId) ?: 2048;
    }

    public function getLlmTemperature(?int $storeId = null): float
    {
        $v = $this->scopeConfig->getValue('aicommerce/llm/temperature', ScopeInterface::SCOPE_STORE, $storeId);
        return $v === null || $v === '' ? 0.7 : (float)$v;
    }

    public function isChannelEnabled(string $channel, ?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag("aicommerce/channels/{$channel}_enabled", ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getChannelSecret(string $path, ?int $storeId = null): string
    {
        $encrypted = (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        return $encrypted ? $this->encryptor->decrypt($encrypted) : '';
    }

    public function getScalar(string $path, ?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
