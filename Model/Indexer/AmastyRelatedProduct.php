<?php

namespace Bluethink\MostviewedIndexer\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewInterface;
use Divante\VsbridgeIndexerCore\Indexer\GenericIndexerHandler;
use Divante\VsbridgeIndexerCore\Indexer\StoreManager;
use Bluethink\MostviewedIndexer\Model\Indexer\Action\AmastyRelatedProductAction as AmastyRelatedProductIndexerAction;

/**
 * Class AmastyRelatedProduct
 */
class AmastyRelatedProduct implements IndexerInterface, MviewInterface
{
    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var GenericIndexerHandler
     */
    private $indexHandler;

    /**
     * @var AmastyRelatedProductIndexerAction
     */
    private $amastyRelatedProductIndexerAction;

    /**
     * AmastyRelatedProduct Constructor
     *
     * @param GenericIndexerHandler $indexerHandler
     * @param StoreManager $storeManager
     * @param AmastyRelatedProductIndexerAction $action
     */
    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        AmastyRelatedProductIndexerAction $action
    ) {
        $this->indexHandler = $indexerHandler;
        $this->storeManager = $storeManager;
        $this->amastyRelatedProductIndexerAction = $action;
    }

    /**
     * @return void
     * @throws \Divante\VsbridgeIndexerCore\Exception\ConnectionUnhealthyException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeFull()
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->indexHandler->saveIndex($this->amastyRelatedProductIndexerAction->rebuild($store->getId()), $store);
            $this->indexHandler->cleanUpByTransactionKey($store);
        }

    }

    /**
     * @param array $ids
     * @return void
     * @throws \Divante\VsbridgeIndexerCore\Exception\ConnectionUnhealthyException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * @param $ids
     * @return void
     * @throws \Divante\VsbridgeIndexerCore\Exception\ConnectionUnhealthyException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($ids)
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->indexHandler->saveIndex($this->amastyRelatedProductIndexerAction->rebuild($store->getId(), $ids),
                $store);
            $this->indexHandler->cleanUpByTransactionKey($store, $ids);
        }

    }

    /**
     * @param $id
     * @return void
     * @throws \Divante\VsbridgeIndexerCore\Exception\ConnectionUnhealthyException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
