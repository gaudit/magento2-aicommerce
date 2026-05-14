<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Llm;

use Gaudit\AiCommerce\Api\Data\LlmRequestInterface;
use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;
use Gaudit\AiCommerce\Api\LlmClientInterface;
use Gaudit\AiCommerce\Exception\LlmException;

/**
 * OpenAI Responses/Chat-Completions client.
 *
 * STATUS: skeleton — accepts contributions. The Anthropic client (see ClaudeClient)
 * is fully implemented and serves as the reference.
 *
 * To implement:
 *  1. Map Gaudit normalized messages → OpenAI `messages` array with `role`+`content`.
 *  2. Translate $request->getTools() to OpenAI `tools` (function calling) schema.
 *  3. POST https://api.openai.com/v1/chat/completions with Bearer auth.
 *  4. Parse response: extract assistant text and tool_calls into normalized LlmResponse.
 *
 * See: https://platform.openai.com/docs/api-reference/chat
 */
class OpenAiClient implements LlmClientInterface
{
    public function getProviderId(): string
    {
        return 'openai';
    }

    public function supportsToolUse(): bool
    {
        return true;
    }

    public function chat(LlmRequestInterface $request): LlmResponseInterface
    {
        throw new LlmException(__('OpenAI client not yet implemented. PRs welcome at https://github.com/gaudit/magento2-aicommerce'));
    }
}
