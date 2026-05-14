<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Api\Data;

interface MessageInterface
{
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_TOOL = 'tool';
    public const ROLE_SYSTEM = 'system';

    public function getRole(): string;

    public function getContent(): string;

    /**
     * @return array<int, array{id: string, name: string, input: array}>
     */
    public function getToolCalls(): array;

    /**
     * @return array<int, array{tool_use_id: string, content: string|array}>
     */
    public function getToolResults(): array;
}
