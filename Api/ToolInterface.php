<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api;

/**
 * Contract for a callable tool exposed to LLMs.
 *
 * Register your tool in di.xml under Gaudit\AiCommerce\Model\ToolRegistry
 * arguments. The module discovers tools through DI — no central list to edit.
 */
interface ToolInterface
{
    /**
     * Unique tool identifier (snake_case). Used by LLMs to call it.
     * Example: "search_products", "add_to_cart".
     */
    public function getName(): string;

    /**
     * Human-readable description shown to the LLM as part of the tool prompt.
     * Be precise — the LLM uses this to decide *when* to call the tool.
     */
    public function getDescription(): string;

    /**
     * JSON Schema (draft-7 compatible) describing the input.
     * Same format expected by Anthropic, OpenAI, and Gemini tool definitions.
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool. Return value must be JSON-serializable.
     * Throw \Gaudit\AiCommerce\Exception\ToolExecutionException on failure.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>|string
     */
    public function execute(array $input);

    /**
     * Whether this tool is enabled for the current store/context.
     * Use to gate tools by store_id, customer group, ACL, etc.
     */
    public function isEnabled(?int $storeId = null): bool;
}
