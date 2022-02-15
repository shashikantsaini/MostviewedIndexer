<?php

namespace Bluethink\MostviewedIndexer\Model\Indexer\Action;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category as CategoryResourceModel;
use Bluethink\MostviewedIndexer\Model\Indexer\Action\Category\CategoryData;

/**
 * Class AmastyRelatedCategoryAction
 */
class AmastyRelatedCategoryAction
{
    /**
     * @var CategoryResourceModel
     */
    private $resourceModel;

    /**
     * @var CategoryData
     */
    private $categoryData;

    /**
     * AmastyRelatedCategoryAction Constructor
     *
     * @param CategoryResourceModel $resourceModel
     * @param CategoryData $categoryData
     */
    public function __construct(
        CategoryResourceModel $resourceModel,
        CategoryData $categoryData
    ) {
        $this->resourceModel = $resourceModel;
        $this->categoryData = $categoryData;
    }

    /**
     * @param $storeId
     * @param array $categoryIds
     * @return \Generator
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuild($storeId = 1, array $categoryIds = [])
    {
        $lastCategoryId = 0;

        if (!empty($categoryIds)) {
            $categoryIds = $this->resourceModel->getParentIds($categoryIds);
        }

        do {
            $categories = $this->resourceModel->getCategories($storeId, $categoryIds, $lastCategoryId);

            foreach ($categories as $category) {
                $lastCategoryId = $category['entity_id'];
                $category['id'] = (int)$category['entity_id'];

                if (isset($category['amlanding_is_dynamic'])) {
                    $category['amlanding_is_dynamic'] = (int)$category['amlanding_is_dynamic'];
                }

                $category = $this->categoryData->addAmastycategoryRelatedProducts($category, $storeId);

                if (!isset($category['amasty_product_links'])) {
                    unset($category);
                    continue;
                }

                unset($category['created_at'],
                    $category['updated_at'],
                    $category['attribute_set_id'],
                    $category['parent_id'],
                    $category['path'],
                    $category['position'],
                    $category['level'],
                    $category['children_count']
                );

                yield $lastCategoryId => $category;
            }
        } while (!empty($categories));
    }
}
