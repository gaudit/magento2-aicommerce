<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model;

use Gaudit\AiCommerce\Api\ToolInterface;
use Gaudit\AiCommerce\Api\ToolRegistryInterface;

class ToolRegistry implements ToolRegistryInterface
{
    /**
     * @param ToolInterface[] $tools keyed by tool name (matches di.xml item names)
     */
    public function __construct(private readonly array $tools = [])
    {
    }

    public function getAll(?int $storeId = null): array
    {
        $enabled = [];
        foreach ($this->tools as $tool) {
            if (!$tool instanceof ToolInterface) {
                continue;
            }
            if (!$tool->isEnabled($storeId)) {
                continue;
            }
            $enabled[$tool->getName()] = $tool;
        }
        return $enabled;
    }

    public function get(string $name): ?ToolInterface
    {
        foreach ($this->tools as $tool) {
            if ($tool instanceof ToolInterface && $tool->getName() === $name) {
                return $tool;
            }
        }
        return null;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }
}
