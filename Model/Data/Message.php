<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Data;

use Gaudit\AiCommerce\Api\Data\MessageInterface;

class Message implements MessageInterface
{
    public function __construct(
        private readonly string $role,
        private readonly string $content = '',
        private readonly array $toolCalls = [],
        private readonly array $toolResults = []
    ) {
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    public function getToolResults(): array
    {
        return $this->toolResults;
    }

    public static function user(string $content): self
    {
        return new self(MessageInterface::ROLE_USER, $content);
    }

    public static function assistant(string $content, array $toolCalls = []): self
    {
        return new self(MessageInterface::ROLE_ASSISTANT, $content, $toolCalls);
    }

    public static function tool(array $toolResults): self
    {
        return new self(MessageInterface::ROLE_TOOL, '', [], $toolResults);
    }
}
