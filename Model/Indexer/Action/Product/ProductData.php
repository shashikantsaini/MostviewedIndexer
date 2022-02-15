<?php

namespace Bluethink\MostviewedIndexer\Model\Indexer\Action\Product;

use Amasty\Mostviewed\Model\Rule\Condition\Product;
use Amasty\Mostviewed\Model\Rule\Condition\SameAsCombine;
use Bluethink\MostviewedIndexer\Model\ResourceModel\AmastyRelatedProducts as AmastyResourceModel;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class ProductData
 */
class ProductData
{
    /**
     * @var AmastyResourceModel
     */
    private $amastyResourceModel;
    /**
     * @var ProductRepositoryInterface
     *
     * /**
     * @var array $cartRelatedProducts
     */
    private $cartRelatedProducts = [];

    /**
     * @var array $productRelatedProducts
     */
    private $productRelatedProducts = [];

    /**
     * ProductData constructor.
     *
     * @param AmastyResourceModel $amastyResourceModel
     */
    public function __construct(
        AmastyResourceModel $amastyResourceModel
    ) {
        $this->amastyResourceModel = $amastyResourceModel;
    }

    /**
     * @param $product
     * @param $storeId
     * @return array
     * @throws Exception
     */
    public function addAmastyProductRelatedProducts($product, $storeId)
    {
        $amastyRelatedProducts = $this->getProductRelatedProducts($storeId);

        foreach ($amastyRelatedProducts as $position => $group) {
            foreach ($group as $groupRule) {
                /**
                 * Loop through each rule position
                 */
                if (in_array($product['id'], $groupRule['where_show'])) {
                    if ($position !== 'product_page_similar_products') {
                        unset($groupRule['conditions_serialized']);
                    }
                    unset($groupRule['where_show']);
                    $rule = $groupRule['group_object'];
                    unset($groupRule['group_object']);

                    if ($rule->getSameAs()) {
                        $conditions = [];

                        /* @var SameAsCombine $combineConditions */
                        $combineConditions = $rule->getSameAsConditions();
                        if ($combineConditions && is_array($combineConditions->getData('same_as_conditions'))) {
                            $conditions = $combineConditions->getData('same_as_conditions');
                        }

                        foreach ($conditions as $sameAsCondition) {
                            /* @var Product $sameAsCondition */
                            if (isset($product[$sameAsCondition->getAttribute()])) {
                                if (!isset($groupRule['same_as'])) {
                                    $groupRule['same_as'] = [];
                                }

                                $value = is_array($product[$sameAsCondition->getAttribute()])
                                    ? implode(',', $product[$sameAsCondition->getAttribute()]) :
                                    (string)$product[$sameAsCondition->getAttribute()];

                                $groupRule['same_as'][] = [
                                    'attribute' => $sameAsCondition->getAttribute(),
                                    'condition' => $sameAsCondition->getOperator() === '==' ? 'eq' : 'neq',
                                    'value' => $value
                                ];
                            }
                        }
                    }

                    if (!isset($product['amasty_product_links'][$position])) {
                        $product['amasty_product_links'][$position] = [];
                    }
                    $product['amasty_product_links'][$position][] = $groupRule;
                }
            }
        }

        return $product;
    }

    /**
     * @param $storeId
     * @return array|mixed
     * @throws Exception
     */
    public function getProductRelatedProducts($storeId)
    {
        if (!isset($this->productRelatedProducts[$storeId])) {
            $this->productRelatedProducts[$storeId] = $this->amastyResourceModel->getAmastyRelatedProducts('product',
                $storeId);
        }

        return $this->productRelatedProducts[$storeId];
    }

    /**
     * @param $product
     * @param $storeId
     * @return array
     * @throws Exception
     */
    public function addAmastyCartRelatedProducts($product, $storeId)
    {
        $amastyCartRelatedProducts = $this->getCartRelatedProducts($storeId);

        foreach ($amastyCartRelatedProducts as $position => $group) {
            foreach ($group as  $groupRule) {
                if (in_array($product['id'], $groupRule['where_show'])) {
                    unset($groupRule['where_show']);
                    unset($groupRule['group_object']);
                    if (!isset($product['amasty_product_links'][$position])) {
                        $product['amasty_product_links'][$position] = [];
                    }
                    $product['amasty_product_links'][$position][] = $groupRule;
                }
            }
        }

        return $product;
    }

    /**
     * @param $storeId
     * @return array|mixed
     * @throws Exception
     */
    public function getCartRelatedProducts($storeId)
    {
        if (!isset($this->cartRelatedProducts[$storeId])) {
            $this->cartRelatedProducts[$storeId] = $this->amastyResourceModel->getAmastyRelatedProducts('cart',
                $storeId);
        }

        return $this->cartRelatedProducts[$storeId];
    }
}
