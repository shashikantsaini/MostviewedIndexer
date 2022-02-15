<?php

namespace Bluethink\MostviewedIndexer\Model\Indexer\Action;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product as ProductResourceModel;
use Bluethink\MostviewedIndexer\Model\Indexer\Action\Product\ProductData;

/**
 * Class AmastyRelatedProductAction
 */
class AmastyRelatedProductAction
{
    /**
     * @var ProductResourceModel
     */
    private $resourceModel;

    /**
     * @var ProductData
     */
    private $productData;

    /**
     * AmastyRelatedProductAction Constructor
     *
     * @param ProductResourceModel $resourceModel
     * @param ProductData $productData
     */
    public function __construct(
        ProductResourceModel $resourceModel,
        ProductData $productData
    ) {
        $this->resourceModel = $resourceModel;
        $this->productData = $productData;
    }

    /**
     * @param $storeId
     * @param array $productIds
     * @return \Generator
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuild($storeId = 1, array $productIds = [])
    {
        $lastProductId = 0;

        // Ensure to reindex also the parents product ids
        if (!empty($productIds)) {
            $productIds = $this->getProductIds($productIds);
        }

        do {
            $products = $this->resourceModel->getProducts($storeId, $productIds, $lastProductId);

            /** @var array $product */
            foreach ($products as $product) {
                $lastProductId = (int)$product['entity_id'];
                $product['id'] = $lastProductId;


                $product = $this->productData->addAmastyProductRelatedProducts($product, $storeId);
                $product = $this->productData->addAmastyCartRelatedProducts($product, $storeId);

                if (!isset($product['amasty_product_links'])) {
                    unset($product);
                    continue;
                }

                unset($product['required_options'],
                    $product['has_options'],
                    $product['created_at'],
                    $product['updated_at'],
                    $product['attribute_set_id'],
                    $product['type_id'],
                    $product['sku']
                );

                yield $lastProductId => $product;
            }
        } while (!empty($products));
    }

    /**
     * @param array $childrenIds
     * @return array
     * @throws \Exception
     */
    private function getProductIds(array $childrenIds)
    {
        $parentIds = $this->resourceModel->getRelationsByChild($childrenIds);

        if (!empty($parentIds)) {
            $parentIds = array_map('intval', $parentIds);
        }

        return array_unique(array_merge($childrenIds, $parentIds));
    }

}
