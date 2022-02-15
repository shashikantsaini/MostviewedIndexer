<?php

namespace Bluethink\MostviewedIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Amasty\Mostviewed\Api\GroupRepositoryInterface;
use Amasty\Mostviewed\Model\ResourceModel\Product\Collection;
use Amasty\Mostviewed\Model\ResourceModel\Product\CollectionFactory;
use Amasty\Mostviewed\Model\ResourceModel\RuleIndex;
use Bluethink\MostViewed\Model\OptionSource\BlockPosition;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class AmastyRelatedProducts
 */
class AmastyRelatedProducts
{
    /**
     * Maximum allowed products
     */
    public const AMASTY_MAX_RELATED_PRODUCTS = 100;

    /**
     * Products per collection page
     */
    public const AMASTY_PRODUCT_COLLECTION_PAGE_SIZE = 100;
    /**
     * Alias for catalog_product_entity table
     */
    const MAIN_TABLE_ALIAS = 'entity';
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var RuleIndex
     */
    private $indexResource;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * AmastyRelatedProducts Constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $productCollectionFactory
     * @param RuleIndex $indexResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $productCollectionFactory,
        RuleIndex $indexResource,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->indexResource = $indexResource;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $type
     * @param $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getAmastyRelatedProducts(string $type, $storeId)
    {
        /**
         * Fix for applyProductsFilterToCollection method, that is using storeManager to get store id instead of $storeId parameter
         */
        $this->storeManager->setCurrentStore($storeId);

        $groupsSearchCriteria = $this->searchCriteriaBuilder
            ->addFilter('block_position', sprintf('%s_%%', $type), 'like')
            ->addFilter('status', 1)
            ->create();

        try {
            $result = [];
            $groupIds = [];
            $groups = $this->groupRepository->getList($groupsSearchCriteria);

            foreach ($groups->getItems() as $group) {
                $groupIds[] = $group->getData('group_id');
                if (!isset($result[$group->getData('block_position')])) {
                    $result[$group->getData('block_position')] = [];
                }
                $result[$group->getData('block_position')][$group->getData('group_id')] = [
                    'sorting' => $group->getData('sorting'),
                    'block_title' => $group->getData('block_title'),
                    'max_products' => $group->getData('max_products'),
                    'show_out_of_stock' => $group->getData('show_out_of_stock'),
                    'customer_group_ids' => $group->getData('customer_group_ids'),
                    'priority' => $group->getData('priority'),
                    'products' => [],
                    'where_show' => [],
                    'group_object' => $group,
                    'rule_id' => $group->getData('group_id'),
                    'conditions_serialized' => $group->getData('conditions_serialized')
                ];

                $productCollection = $this->getProductCollection($group, $storeId);
                $page = 1;
                $lastPage = max(1,
                    floor(self::AMASTY_MAX_RELATED_PRODUCTS / self::AMASTY_PRODUCT_COLLECTION_PAGE_SIZE));
                while ($page <= $lastPage) {
                    $collection = $productCollection->getData();
                    foreach ($collection as $product) {
                        $result[$group->getData('block_position')][$group->getData('group_id')]['products'][] = [
                            'sku' => $product['sku'],
                            'product_type' => $product['type_id'],
                        ];
                    }
                    $productCollection->setCurPage(++$page)->resetData();
                }
            }

            $connection = $this->resourceConnection->getConnection();
            $entities = $connection->select()->from($connection->getTableName('amasty_mostviewed_product_index'))
                ->where('store_id = ?', $storeId)
                ->where('rule_id IN (?)', $groupIds)
                ->columns(['entity_id', 'rule_id', 'position'])
                ->query()
                ->fetchAll();

            foreach ($entities as $entity) {
                if ($entity['relation'] === 'where_show') {
                    $result[$entity['position']][$entity['rule_id']]['where_show'][] = $entity['entity_id'];
                    continue;
                }
            }

            return $result;
        } catch (NoSuchEntityException $e) {
            throw new Exception('Could not load amasty_mostviewed groups/rules');
        }
    }

    /**
     * @param $group
     * @param $storeId
     * @param $isPaginated
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductCollection($group, $storeId, $isPaginated = true)
    {
        /**
         * @var $collection Collection
         */
        $collection = $this->productCollectionFactory->create()
            ->setStoreId($storeId);

        if ($group->getBlockPosition() == BlockPosition::PRODUCT_POPULAR_COLLECTIONS) {
            $collection->addAttributeToFilter('type_id', ['neq' => 'simple']);
            $collection->addAttributeToFilter('type_id', ['neq' => 'bundle']);
        } else {
            $collection->addAttributeToFilter('type_id', ['neq' => 'collection']);
        }

        if ($conditions = $group->getConditions()->getConditions()) {
            $this->indexResource->applyProductsFilterToCollection($collection, $group->getGroupId());
        }

        if ($isPaginated) {
            $collection->setPageSize(self::AMASTY_PRODUCT_COLLECTION_PAGE_SIZE);
            $collection->setCurPage(1);
        }

        return $collection;
    }
}
