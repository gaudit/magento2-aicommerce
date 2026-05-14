<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api;

use Gaudit\AiCommerce\Api\Data\LlmRequestInterface;
use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;

/**
 * Contract for an LLM provider client.
 *
 * Implementations: Claude (Anthropic), OpenAI, Gemini.
 * Add yours by binding to this interface in di.xml with a unique virtual type
 * and listing it in the system.xml provider selector.
 */
interface LlmClientInterface
{
    /**
     * Provider identifier ("anthropic", "openai", "gemini", ...).
     */
    public function getProviderId(): string;

    /**
     * Execute one round-trip with the LLM. Tools are passed in normalized form;
     * the implementation is responsible for translating to the provider schema.
     */
    public function chat(LlmRequestInterface $request): LlmResponseInterface;

    /**
     * Whether this provider supports tool/function calling.
     */
    public function supportsToolUse(): bool;
}
