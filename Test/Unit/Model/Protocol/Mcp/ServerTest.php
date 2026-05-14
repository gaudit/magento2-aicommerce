<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Test\Unit\Model\Protocol\Mcp;

use Gaudit\AiCommerce\Api\ToolInterface;
use Gaudit\AiCommerce\Api\ToolRegistryInterface;
use Gaudit\AiCommerce\Model\Protocol\Mcp\Server;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testInitializeReturnsCapabilities(): void
    {
        $registry = $this->createMock(ToolRegistryInterface::class);
        $server = new Server($registry);

        $response = $server->handle([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [],
        ]);

        $this->assertSame('2.0', $response['jsonrpc']);
        $this->assertSame(1, $response['id']);
        $this->assertSame('2025-03-26', $response['result']['protocolVersion']);
        $this->assertSame('gaudit-aicommerce', $response['result']['serverInfo']['name']);
    }

    public function testToolsListReflectsRegistry(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('search_products');
        $tool->method('getDescription')->willReturn('Search products.');
        $tool->method('getInputSchema')->willReturn(['type' => 'object']);
        $tool->method('isEnabled')->willReturn(true);

        $registry = $this->createMock(ToolRegistryInterface::class);
        $registry->method('getAll')->willReturn(['search_products' => $tool]);

        $server = new Server($registry);
        $response = $server->handle([
            'jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list',
        ]);

        $tools = $response['result']['tools'];
        $this->assertCount(1, $tools);
        $this->assertSame('search_products', $tools[0]['name']);
    }

    public function testUnknownMethodReturnsJsonRpcError(): void
    {
        $server = new Server($this->createMock(ToolRegistryInterface::class));
        $response = $server->handle([
            'jsonrpc' => '2.0', 'id' => 3, 'method' => 'does/not/exist',
        ]);
        $this->assertSame(-32601, $response['error']['code']);
    }
}
