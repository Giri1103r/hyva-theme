<?php
declare(strict_types=1);

namespace Codilar\HomePage\Block;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Home extends Template
{
    /**
     * @var CategoryInterface[]|null
     */
    private ?array $topCategories = null;

    public function __construct(
        Context $context,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Active top-level (level 2) categories under the current store's root category.
     *
     * @return CategoryInterface[]
     */
    public function getTopCategories(): array
    {
        if ($this->topCategories === null) {
            $rootCategoryId = (int)$this->storeManager->getStore()->getRootCategoryId();

            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'url_key'])
                ->addFieldToFilter('parent_id', $rootCategoryId)
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('level', 2)
                ->setOrder('position', 'ASC');

            $this->topCategories = $collection->getItems();
        }

        return $this->topCategories;
    }

    public function getCategoryUrl(CategoryInterface $category): string
    {
        return $category->getUrl();
    }

    /**
     * Load a small, explicit set of products (used for the hero image collage).
     *
     * @param int[] $productIds
     * @return ProductInterface[]
     */
    public function getProductsByIds(array $productIds): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'small_image', 'image'])
            ->addFieldToFilter('entity_id', ['in' => $productIds])
            ->addFieldToFilter('status', 1);

        $items = $collection->getItems();

        $ordered = [];
        foreach ($productIds as $id) {
            if (isset($items[$id])) {
                $ordered[] = $items[$id];
            }
        }

        return $ordered;
    }

    public function getProductImageUrl(ProductInterface $product, string $imageId = 'category_page_grid'): string
    {
        return $this->imageHelper->init($product, $imageId)->getUrl();
    }
}
