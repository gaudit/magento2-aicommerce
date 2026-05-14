<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Data;

use Gaudit\AiCommerce\Api\Data\LlmRequestInterface;

class LlmRequest implements LlmRequestInterface
{
    public function __construct(
        private readonly string $systemPrompt,
        private readonly array $messages,
        private readonly array $tools = [],
        private readonly string $model = '',
        private readonly int $maxTokens = 2048,
        private readonly float $temperature = 0.7,
        private readonly ?int $storeId = null
    ) {
    }

    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getStoreId(): ?int
    {
        return $this->storeId;
    }
}
