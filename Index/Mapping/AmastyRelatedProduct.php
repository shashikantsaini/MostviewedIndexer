<?php declare(strict_types=1);

namespace Bluethink\MostviewedIndexer\Index\Mapping;

use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;
use Divante\VsbridgeIndexerCore\Api\MappingInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\DataObject;

/**
 * Class AmastyRelatedProduct
 */
class AmastyRelatedProduct implements MappingInterface
{

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var string
     */
    private $type;

    /**
     * Brands constructor.
     *
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function getMappingProperties()
    {
        $properties = [
            'id' => ['type' => FieldInterface::TYPE_INTEGER],
            'amasty_product_links' => ['type' => FieldInterface::TYPE_TEXT],
        ];

        $mappingObject = new DataObject();
        $mappingObject->setData('properties', $properties);

        $this->eventManager->dispatch(
            'vsbridge_amasty_related_products_mapping_properties',
            ['mapping' => $mappingObject]
        );

        return $mappingObject->getData();
    }
}
