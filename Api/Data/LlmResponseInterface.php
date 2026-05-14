<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api\Data;

interface LlmResponseInterface
{
    /**
     * Final assistant text (null if the response was tool-use only).
     */
    public function getText(): ?string;

    /**
     * Pending tool calls the orchestrator must execute before continuing.
     *
     * @return array<int, array{id: string, name: string, input: array}>
     */
    public function getToolCalls(): array;

    /**
     * Provider stop reason ("end_turn", "tool_use", "max_tokens", ...).
     */
    public function getStopReason(): string;

    /**
     * @return array{input_tokens: int, output_tokens: int}
     */
    public function getUsage(): array;
}
