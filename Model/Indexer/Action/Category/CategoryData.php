<?php

namespace Bluethink\MostviewedIndexer\Model\Indexer\Action\Category;

use Bluethink\MostviewedIndexer\Model\ResourceModel\AmastyRelatedProducts as AmastyResourceModel;
use Magebit\StaticContentProcessor\Helper\Resolver;

/**
 * Class CategoryData
 */
class CategoryData
{
    /**
     * @var AmastyResourceModel
     */
    private $amastyResourceModel;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * CategoryData constructor.
     *
     * @param AmastyResourceModel $amastyResourceModel
     * @param Resolver $resolver
     */
    public function __construct(
        AmastyResourceModel $amastyResourceModel,
        Resolver $resolver
    ) {
        $this->amastyResourceModel = $amastyResourceModel;
        $this->resolver = $resolver;
    }

    /**
     * @param $category
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addAmastycategoryRelatedProducts($category, $storeId)
    {
        $amastyRelatedProducts = $this->amastyResourceModel->getAmastyRelatedProducts('category', $storeId);

        foreach ($amastyRelatedProducts as $position => $group) {
            foreach ($group as $groupRule) {
                if (in_array($category['id'], $groupRule['where_show'])) {
                    unset($groupRule['where_show']);
                    unset($groupRule['group_object']);
                    $category['amasty_product_links'][$position] = $groupRule;
                }
            }
        }
        if (isset($category['description']) && is_string($category['description'])) {
            $category['description'] = $this->resolver->resolve($category['description'], $storeId);
        }

        return $category;
    }
}
