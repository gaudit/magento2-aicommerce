<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Llm;

use Gaudit\AiCommerce\Api\Data\LlmRequestInterface;
use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;
use Gaudit\AiCommerce\Api\LlmClientInterface;
use Gaudit\AiCommerce\Exception\LlmException;

/**
 * Google Gemini (Generative Language API) client.
 *
 * STATUS: skeleton.
 *
 * To implement:
 *  1. Convert normalized messages to Gemini `contents` array (role: user|model, parts: [{text}]).
 *  2. Convert tools to Gemini `tools.functionDeclarations`.
 *  3. POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={apiKey}
 *  4. Parse `functionCall` parts as tool calls; `text` parts as assistant text.
 *
 * See: https://ai.google.dev/api/generate-content#function_calling
 */
class GeminiClient implements LlmClientInterface
{
    public function getProviderId(): string
    {
        return 'gemini';
    }

    public function supportsToolUse(): bool
    {
        return true;
    }

    public function chat(LlmRequestInterface $request): LlmResponseInterface
    {
        throw new LlmException(__('Gemini client not yet implemented. PRs welcome at https://github.com/gaudit/magento2-aicommerce'));
    }
}
