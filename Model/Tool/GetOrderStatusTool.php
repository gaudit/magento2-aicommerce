<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Tool;

use Gaudit\AiCommerce\Exception\ToolExecutionException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class GetOrderStatusTool extends AbstractTool
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function getName(): string
    {
        return 'get_order_status';
    }

    public function getDescription(): string
    {
        return 'Look up the status of an order by increment_id (the customer-visible order number). Returns status, total, items, tracking if shipped.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'increment_id' => ['type' => 'string', 'description' => 'Order number shown to the customer (e.g. "000000123")'],
            ],
            'required' => ['increment_id'],
        ];
    }

    public function execute(array $input): array
    {
        $incrementId = trim((string)($input['increment_id'] ?? ''));
        if ($incrementId === '') {
            throw new ToolExecutionException(__('increment_id is required.'));
        }

        $criteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId)->create();
        $orders = $this->orderRepository->getList($criteria)->getItems();
        if (empty($orders)) {
            throw new ToolExecutionException(__('Order not found: %1', $incrementId));
        }
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = reset($orders);

        $items = [];
        foreach ($order->getItems() as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'qty' => (float)$item->getQtyOrdered(),
                'row_total' => (float)$item->getRowTotal(),
            ];
        }

        return [
            'increment_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'state' => $order->getState(),
            'grand_total' => (float)$order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'created_at' => $order->getCreatedAt(),
            'items' => $items,
        ];
    }
}
