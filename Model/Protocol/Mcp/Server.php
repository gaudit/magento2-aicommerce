<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Protocol\Mcp;

use Gaudit\AiCommerce\Api\ToolRegistryInterface;

/**
 * Minimal MCP (Model Context Protocol) server.
 *
 * Implements the streamable-HTTP transport variant: a single endpoint accepting
 * JSON-RPC 2.0 requests over POST. Methods supported:
 *   - initialize
 *   - tools/list
 *   - tools/call
 *   - ping
 *
 * Compatible with Claude Desktop, Cursor, and any spec-conformant MCP client
 * via the `streamable-http` transport.
 *
 * Spec: https://modelcontextprotocol.io/specification/2025-03-26
 */
class Server
{
    private const PROTOCOL_VERSION = '2025-03-26';
    private const SERVER_NAME = 'gaudit-aicommerce';
    private const SERVER_VERSION = '0.1.0';

    public function __construct(
        private readonly ToolRegistryInterface $toolRegistry
    ) {
    }

    /**
     * Handle a JSON-RPC request. Returns the response (or null for notifications).
     */
    public function handle(array $request, ?int $storeId = null): ?array
    {
        $id = $request['id'] ?? null;
        $method = (string)($request['method'] ?? '');
        $params = (array)($request['params'] ?? []);

        try {
            switch ($method) {
                case 'initialize':
                    return $this->ok($id, $this->initialize($params));
                case 'tools/list':
                    return $this->ok($id, $this->toolsList($storeId));
                case 'tools/call':
                    return $this->ok($id, $this->toolsCall($params, $storeId));
                case 'ping':
                    return $this->ok($id, new \stdClass());
                case 'notifications/initialized':
                case 'notifications/cancelled':
                    return null;
                default:
                    return $this->error($id, -32601, "Method not found: {$method}");
            }
        } catch (\Throwable $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    private function initialize(array $params): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => new \stdClass(),
            ],
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
        ];
    }

    private function toolsList(?int $storeId): array
    {
        $tools = [];
        foreach ($this->toolRegistry->getAll($storeId) as $tool) {
            $tools[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema(),
            ];
        }
        return ['tools' => $tools];
    }

    private function toolsCall(array $params, ?int $storeId): array
    {
        $name = (string)($params['name'] ?? '');
        $args = (array)($params['arguments'] ?? []);

        $tool = $this->toolRegistry->get($name);
        if (!$tool || !$tool->isEnabled($storeId)) {
            return [
                'content' => [['type' => 'text', 'text' => "Tool not available: {$name}"]],
                'isError' => true,
            ];
        }

        try {
            $result = $tool->execute($args);
            $text = is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return [
                'content' => [['type' => 'text', 'text' => $text]],
                'isError' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'content' => [['type' => 'text', 'text' => $e->getMessage()]],
                'isError' => true,
            ];
        }
    }

    private function ok($id, $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error($id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
