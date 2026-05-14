<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Tool;

use Gaudit\AiCommerce\Exception\ToolExecutionException;

/**
 * Add a product to a (guest or customer) quote.
 *
 * STATUS: skeleton — wire to Magento\Quote\Api\GuestCartManagementInterface
 * and GuestCartItemRepositoryInterface. Conversation should carry a stable
 * cart_id (mask) provisioned on first turn.
 *
 * The Node bot equivalent: bot/src/orchestrator/tools/add-to-cart.ts
 */
class AddToCartTool extends AbstractTool
{
    public function getName(): string
    {
        return 'add_to_cart';
    }

    public function getDescription(): string
    {
        return 'Add a product to the current cart by SKU. Use after the customer confirms intent to buy.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sku' => ['type' => 'string'],
                'quantity' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
            ],
            'required' => ['sku'],
        ];
    }

    public function execute(array $input): array
    {
        throw new ToolExecutionException(__('add_to_cart not yet wired. Reference: bot/src/orchestrator/tools/add-to-cart.ts'));
    }
}
