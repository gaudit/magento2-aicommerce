<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model;

use Gaudit\AiCommerce\Api\Data\LlmResponseInterface;
use Gaudit\AiCommerce\Api\Data\MessageInterface;
use Gaudit\AiCommerce\Api\ToolRegistryInterface;
use Gaudit\AiCommerce\Exception\LlmException;
use Gaudit\AiCommerce\Exception\ToolExecutionException;
use Gaudit\AiCommerce\Model\Data\LlmRequest;
use Gaudit\AiCommerce\Model\Data\Message;
use Gaudit\AiCommerce\Model\Llm\LlmClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Turn-by-turn orchestrator: runs the LLM loop with tool execution.
 *
 * Loop:
 *  1. Send messages + tools to LLM.
 *  2. If response is `end_turn`, return text.
 *  3. If `tool_use`, execute each tool, append results, go to 1.
 *  4. Cap by $maxTurns to prevent runaway.
 */
class Orchestrator
{
    private const MAX_TURNS = 8;

    public function __construct(
        private readonly Config $config,
        private readonly LlmClientFactory $llmFactory,
        private readonly ToolRegistryInterface $toolRegistry,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param MessageInterface[] $history Prior messages (most recent last).
     * @return array{text: string, usage: array, turns: int}
     */
    public function run(string $userMessage, array $history, int $storeId): array
    {
        if (!$this->config->isEnabled($storeId)) {
            throw new LlmException(__('AI Commerce is disabled for this store.'));
        }

        $client = $this->llmFactory->forStore($storeId);
        $messages = array_merge($history, [Message::user($userMessage)]);
        $tools = $this->serializeTools($storeId);

        $aggregateUsage = ['input_tokens' => 0, 'output_tokens' => 0];
        $turn = 0;
        $finalText = '';

        while ($turn < self::MAX_TURNS) {
            $turn++;
            $request = new LlmRequest(
                $this->resolveSystemPrompt($storeId),
                $messages,
                $tools,
                $this->config->getLlmModel($storeId),
                $this->config->getLlmMaxTokens($storeId),
                $this->config->getLlmTemperature($storeId),
                $storeId
            );

            $response = $client->chat($request);
            $aggregateUsage['input_tokens'] += $response->getUsage()['input_tokens'] ?? 0;
            $aggregateUsage['output_tokens'] += $response->getUsage()['output_tokens'] ?? 0;

            $messages[] = Message::assistant($response->getText() ?? '', $response->getToolCalls());

            if ($response->getStopReason() !== 'tool_use' || empty($response->getToolCalls())) {
                $finalText = $response->getText() ?? '';
                break;
            }

            $toolResults = $this->executeToolCalls($response->getToolCalls(), $storeId);
            $messages[] = Message::tool($toolResults);
        }

        return [
            'text' => $finalText,
            'usage' => $aggregateUsage,
            'turns' => $turn,
        ];
    }

    /**
     * @return array<int, array{tool_use_id: string, content: string}>
     */
    private function executeToolCalls(array $toolCalls, int $storeId): array
    {
        $results = [];
        foreach ($toolCalls as $call) {
            $tool = $this->toolRegistry->get($call['name']);
            if (!$tool) {
                $results[] = [
                    'tool_use_id' => $call['id'],
                    'content' => json_encode(['error' => "unknown tool: {$call['name']}"]),
                    'is_error' => true,
                ];
                continue;
            }
            try {
                $payload = $tool->execute($call['input'] ?? []);
                $results[] = [
                    'tool_use_id' => $call['id'],
                    'content' => is_string($payload) ? $payload : json_encode($payload),
                ];
            } catch (\Throwable $e) {
                $this->logger->error('[aicommerce] tool failed', [
                    'tool' => $call['name'],
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'tool_use_id' => $call['id'],
                    'content' => json_encode(['error' => $e->getMessage()]),
                    'is_error' => true,
                ];
            }
        }
        return $results;
    }

    private function serializeTools(int $storeId): array
    {
        $out = [];
        foreach ($this->toolRegistry->getAll($storeId) as $tool) {
            $out[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'input_schema' => $tool->getInputSchema(),
            ];
        }
        return $out;
    }

    private function resolveSystemPrompt(int $storeId): string
    {
        return $this->config->getSystemPrompt($storeId)
            ?: 'You are a helpful shopping assistant.';
    }
}
