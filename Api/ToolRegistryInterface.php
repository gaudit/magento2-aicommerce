<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api;

/**
 * Registry that exposes all enabled tools to the orchestrator and protocols.
 */
interface ToolRegistryInterface
{
    /**
     * @return ToolInterface[]
     */
    public function getAll(?int $storeId = null): array;

    public function get(string $name): ?ToolInterface;

    public function has(string $name): bool;
}
