<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\State;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;


class patch01 implements DataPatchInterface
{
    protected ModuleDataSetupInterface $setup;
    protected State $state;
    protected ProductInterfaceFactory $productFactory;
    protected ProductRepositoryInterface $productRepository;
    protected CategoryLinkManagementInterface $categoryLink;
    protected CategoryCollectionFactory $categoryCollectionFactory;
    protected EavSetup $eavSetup;

    public function __construct(
        ModuleDataSetupInterface        $setup,
        State                           $state,
        ProductInterfaceFactory         $productFactory,
        ProductRepositoryInterface      $productRepository,
        CategoryLinkManagementInterface $categoryLink,
        CategoryCollectionFactory       $categoryCollectionFactory,
        EavSetup                        $eavSetup
    )
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->eavSetup = $eavSetup;
        $this->categoryLink = $categoryLink;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->setup = $setup;
        $this->state = $state;
    }

    /**
     * @throws \Exception
     */
    public function apply()
    {
        $this->setup->startSetup();
        $this->state->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @throws StateException
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function execute()
    {
        $product = $this->productFactory->create();
        if ($product->getIdBySku('puma_suede')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Puma Suede')
            ->setSku('puma_suede')
            ->setPrice(120)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);

        $product = $this->productRepository->save($product);

        $categoryTitles = ['Men'];
        $categoryIds = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('name', ['in' => $categoryTitles])
            ->getAllIds();

        $this->categoryLink->assignProductToCategories($product->getSku(), $categoryIds);

        $this->setup->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
