<?php

declare(strict_types=1);

namespace Gaudit\AiCommerce\Model\Tool;

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ListCategoriesTool extends AbstractTool
{
    public function __construct(
        private readonly CategoryListInterface $categoryList,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function getName(): string
    {
        return 'list_categories';
    }

    public function getDescription(): string
    {
        return 'List active categories in the store. Use to help the customer browse by section. Returns id, name, level, and parent_id.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parent_id' => ['type' => 'integer', 'description' => 'Filter to children of this category (omit for top-level).'],
            ],
        ];
    }

    public function execute(array $input): array
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('is_active', 1)
            ->addFilter('parent_id', (int)($input['parent_id'] ?? 2))
            ->create();

        $result = $this->categoryList->getList($criteria);
        $items = [];
        foreach ($result->getItems() as $category) {
            $items[] = [
                'id' => (int)$category->getId(),
                'name' => $category->getName(),
                'level' => (int)$category->getLevel(),
                'parent_id' => (int)$category->getParentId(),
            ];
        }
        return ['total_count' => $result->getTotalCount(), 'categories' => $items];
    }
}
