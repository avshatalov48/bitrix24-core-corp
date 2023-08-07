<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;

use Bitrix\Catalog\v2\Facade\Repository;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Product\BaseProduct;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Query;
use Bitrix\Mobile\UI\File;

Loader::includeModule('catalog');

final class SkuCollectionQuery extends Query
{
	private int $variationId;

	private Repository $productsRepository;

	public function __construct(int $variationId)
	{
		$this->variationId = $variationId;
		$this->productsRepository = ServiceContainer::getRepositoryFacade();
	}

	public function execute(): array
	{
		$product = $this->findParentProduct();
		if (!$product)
		{
			return [];
		}

		$variations = [];

		foreach ($product->getSkuCollection() as $sku)
		{
			$gallery = array_values(array_map(
				static fn($item) => File::loadWithPreview($item['ID']),
				$sku->getFrontImageCollection()->toArray()
			));

			$variationId = $sku->getId();
			$variations[$variationId] = [
				'ID' => $variationId,
				'NAME' => $sku->getName(),
				'GALLERY' => $gallery,
			];
		}

		return [
			'variations' => $variations,
		];
	}

	private function findParentProduct(): ?BaseProduct
	{
		$sku = $this->productsRepository->loadVariation($this->variationId);
		if (!$sku)
		{
			return null;
		}

		/** @var BaseProduct $product */
		$product = $sku->getParent();

		return $product;
	}
}
