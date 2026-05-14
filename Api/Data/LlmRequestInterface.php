<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api\Data;

interface LlmRequestInterface
{
    public function getSystemPrompt(): string;

    /**
     * @return MessageInterface[]
     */
    public function getMessages(): array;

    /**
     * @return array<int, array{name: string, description: string, input_schema: array}>
     */
    public function getTools(): array;

    public function getModel(): string;

    public function getMaxTokens(): int;

    public function getTemperature(): float;

    public function getStoreId(): ?int;
}
