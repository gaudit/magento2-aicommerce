<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Tool;

use Gaudit\AiCommerce\Exception\ToolExecutionException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class GetProductDetailsTool extends AbstractTool
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getName(): string
    {
        return 'get_product_details';
    }

    public function getDescription(): string
    {
        return 'Fetch detailed information about a single product by SKU: description, attributes, stock, price tiers, related products.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sku' => ['type' => 'string', 'description' => 'Product SKU'],
            ],
            'required' => ['sku'],
        ];
    }

    public function execute(array $input): array
    {
        $sku = trim((string)($input['sku'] ?? ''));
        if ($sku === '') {
            throw new ToolExecutionException(__('SKU is required.'));
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new ToolExecutionException(__('Product not found: %1', $sku));
        }

        $store = $this->storeManager->getStore();

        return [
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'description' => strip_tags((string)$product->getCustomAttribute('description')?->getValue()),
            'short_description' => strip_tags((string)$product->getCustomAttribute('short_description')?->getValue()),
            'price' => (float)$product->getPrice(),
            'currency' => $store->getCurrentCurrencyCode(),
            'in_stock' => $product->isAvailable(),
            'url' => $store->getBaseUrl() . $product->getUrlKey() . '.html',
        ];
    }
}
