<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Data;

use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;

class LlmResponse implements LlmResponseInterface
{
    public function __construct(
        private readonly ?string $text,
        private readonly array $toolCalls,
        private readonly string $stopReason,
        private readonly array $usage
    ) {
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    public function getStopReason(): string
    {
        return $this->stopReason;
    }

    public function getUsage(): array
    {
        return $this->usage;
    }
}
