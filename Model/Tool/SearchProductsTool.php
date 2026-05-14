<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Tool;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Search products via Magento catalog API (no GraphQL — native PHP).
 */
class SearchProductsTool extends AbstractTool
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
        private readonly FilterGroupBuilder $filterGroupBuilder,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getName(): string
    {
        return 'search_products';
    }

    public function getDescription(): string
    {
        return 'Search products in the store catalog. Use for product discovery, browsing, and filtering. Returns up to 10 products per page with name, SKU, price, stock status, and URL.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Search term (e.g. "notebook gamer", "camisa azul"). Empty string returns featured products.'],
                'price_min' => ['type' => 'number', 'description' => 'Minimum price filter'],
                'price_max' => ['type' => 'number', 'description' => 'Maximum price filter'],
                'page' => ['type' => 'integer', 'description' => 'Page number (default 1)', 'default' => 1],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $input): array
    {
        $query = trim((string)($input['query'] ?? ''));
        $page = max(1, (int)($input['page'] ?? 1));
        $pageSize = 10;

        $filterGroups = [];

        if ($query !== '') {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($this->filterBuilder->setField('name')->setConditionType('like')->setValue("%{$query}%")->create())
                ->create();
        }

        if (isset($input['price_min'])) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($this->filterBuilder->setField('price')->setConditionType('gteq')->setValue((float)$input['price_min'])->create())
                ->create();
        }

        if (isset($input['price_max'])) {
            $filterGroups[] = $this->filterGroupBuilder
                ->addFilter($this->filterBuilder->setField('price')->setConditionType('lteq')->setValue((float)$input['price_max'])->create())
                ->create();
        }

        $filterGroups[] = $this->filterGroupBuilder
            ->addFilter($this->filterBuilder->setField('status')->setValue(1)->create())
            ->create();

        $criteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->setCurrentPage($page)
            ->setPageSize($pageSize)
            ->create();

        $result = $this->productRepository->getList($criteria);
        $store = $this->storeManager->getStore();

        $items = [];
        foreach ($result->getItems() as $product) {
            $items[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => (float)$product->getPrice(),
                'currency' => $store->getCurrentCurrencyCode(),
                'in_stock' => $product->isAvailable(),
                'url' => $store->getBaseUrl() . $product->getUrlKey() . '.html',
                'image' => $product->getMediaGalleryEntries() ? $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage() : null,
            ];
        }

        return [
            'total_count' => $result->getTotalCount(),
            'page' => $page,
            'page_size' => $pageSize,
            'products' => $items,
        ];
    }
}
