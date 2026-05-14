<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Test\Unit\Model;

use Gaudit\AiCommerce\Api\ToolInterface;
use Gaudit\AiCommerce\Model\ToolRegistry;
use PHPUnit\Framework\TestCase;

class ToolRegistryTest extends TestCase
{
    public function testGetReturnsRegisteredTool(): void
    {
        $tool = $this->makeTool('search_products');
        $registry = new ToolRegistry([$tool]);

        $this->assertSame($tool, $registry->get('search_products'));
        $this->assertNull($registry->get('does_not_exist'));
    }

    public function testGetAllFiltersDisabledTools(): void
    {
        $enabled = $this->makeTool('a', true);
        $disabled = $this->makeTool('b', false);
        $registry = new ToolRegistry([$enabled, $disabled]);

        $all = $registry->getAll();
        $this->assertCount(1, $all);
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayNotHasKey('b', $all);
    }

    private function makeTool(string $name, bool $enabled = true): ToolInterface
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn($name);
        $tool->method('isEnabled')->willReturn($enabled);
        return $tool;
    }
}
