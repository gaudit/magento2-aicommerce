<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Llm;

use Gaudit\AiCommerce\Api\Data\LlmRequestInterface;
use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;
use Gaudit\AiCommerce\Api\Data\MessageInterface;
use Gaudit\AiCommerce\Api\LlmClientInterface;
use Gaudit\AiCommerce\Exception\LlmException;
use Gaudit\AiCommerce\Model\Config;
use Gaudit\AiCommerce\Model\Data\LlmResponse;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

/**
 * Anthropic Messages API client with tool use.
 *
 * Spec: https://docs.anthropic.com/en/api/messages
 */
class ClaudeClient implements LlmClientInterface
{
    private const API_BASE = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly Config $config,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getProviderId(): string
    {
        return 'anthropic';
    }

    public function supportsToolUse(): bool
    {
        return true;
    }

    public function chat(LlmRequestInterface $request): LlmResponseInterface
    {
        $apiKey = $this->config->getLlmApiKey($request->getStoreId());
        if (!$apiKey) {
            throw new LlmException(__('Anthropic API key not configured.'));
        }

        $payload = [
            'model' => $request->getModel() ?: 'claude-sonnet-4-6',
            'max_tokens' => $request->getMaxTokens(),
            'temperature' => $request->getTemperature(),
            'system' => $request->getSystemPrompt(),
            'messages' => $this->serializeMessages($request->getMessages()),
        ];

        if (!empty($request->getTools())) {
            $payload['tools'] = $request->getTools();
        }

        $this->curl->setHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ]);
        $this->curl->setOption(CURLOPT_TIMEOUT, 60);
        $this->curl->post(self::API_BASE, json_encode($payload));

        $status = (int)$this->curl->getStatus();
        $body = (string)$this->curl->getBody();

        if ($status < 200 || $status >= 300) {
            $this->logger->error('[aicommerce] anthropic call failed', [
                'status' => $status,
                'body' => substr($body, 0, 1000),
            ]);
            throw new LlmException(__('Anthropic API returned HTTP %1', $status));
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new LlmException(__('Invalid Anthropic response body.'));
        }

        return $this->parseResponse($decoded);
    }

    private function serializeMessages(array $messages): array
    {
        $out = [];
        foreach ($messages as $msg) {
            if (!$msg instanceof MessageInterface) {
                continue;
            }
            if ($msg->getRole() === MessageInterface::ROLE_TOOL) {
                $contentBlocks = [];
                foreach ($msg->getToolResults() as $result) {
                    $contentBlocks[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $result['tool_use_id'],
                        'content' => $result['content'],
                        'is_error' => $result['is_error'] ?? false,
                    ];
                }
                $out[] = ['role' => 'user', 'content' => $contentBlocks];
                continue;
            }
            if ($msg->getRole() === MessageInterface::ROLE_ASSISTANT) {
                $blocks = [];
                if ($msg->getContent() !== '') {
                    $blocks[] = ['type' => 'text', 'text' => $msg->getContent()];
                }
                foreach ($msg->getToolCalls() as $call) {
                    $blocks[] = [
                        'type' => 'tool_use',
                        'id' => $call['id'],
                        'name' => $call['name'],
                        'input' => $call['input'] ?? new \stdClass(),
                    ];
                }
                $out[] = ['role' => 'assistant', 'content' => $blocks];
                continue;
            }
            // user / system (system is sent separately via the `system` top-level)
            if ($msg->getRole() === MessageInterface::ROLE_USER) {
                $out[] = ['role' => 'user', 'content' => $msg->getContent()];
            }
        }
        return $out;
    }

    private function parseResponse(array $body): LlmResponseInterface
    {
        $text = null;
        $toolCalls = [];

        foreach ((array)($body['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text = ($text ?? '') . $block['text'];
            } elseif (($block['type'] ?? '') === 'tool_use') {
                $toolCalls[] = [
                    'id' => (string)$block['id'],
                    'name' => (string)$block['name'],
                    'input' => (array)($block['input'] ?? []),
                ];
            }
        }

        return new LlmResponse(
            $text,
            $toolCalls,
            (string)($body['stop_reason'] ?? 'end_turn'),
            [
                'input_tokens' => (int)($body['usage']['input_tokens'] ?? 0),
                'output_tokens' => (int)($body['usage']['output_tokens'] ?? 0),
            ]
        );
    }
}
