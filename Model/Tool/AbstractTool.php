<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Tool;

use Gaudit\AiCommerce\Api\ToolInterface;

abstract class AbstractTool implements ToolInterface
{
    public function isEnabled(?int $storeId = null): bool
    {
        return true;
    }
}
