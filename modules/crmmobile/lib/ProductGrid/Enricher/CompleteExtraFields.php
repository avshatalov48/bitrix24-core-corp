<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\Catalog\Access\Permission\PermissionDictionary;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Accounting;
use Bitrix\CrmMobile\ProductGrid\ProductRowViewModel;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\PermissionsProvider;
use Bitrix\CatalogMobile\ProductGrid\SkuDataProvider;
use Bitrix\Mobile\UI\File;

Loader::requireModule('iblock');
Loader::requireModule('catalog');
Loader::requireModule('catalogmobile');

final class CompleteExtraFields implements EnricherContract
{
	private Accounting $accounting;

	private PermissionsProvider $permissionsProvider;

	private Item $entity;

	private array $productsInfo = [];

	private array $sections = [];

	public function __construct(Accounting $accounting, PermissionsProvider $permissionsProvider, Item $entity)
	{
		$this->accounting = $accounting;
		$this->permissionsProvider = $permissionsProvider;
		$this->entity = $entity;
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 * @return ProductRowViewModel[]
	 */
	public function enrich(array $rows): array
	{
		$this->loadProductsData($rows);
		$this->loadSections();

		foreach ($rows as &$productRow)
		{
			$productRow->isTaxMode = $this->accounting->isTaxMode();
			$productRow->isPriceEditable = $this->isPriceEditable();
			$productRow->isDiscountEditable = $this->isDiscountEditable();
			$this->completeSkuTree($productRow);
			$this->completeSections($productRow);
			$this->completeGallery($productRow);
			$this->completeBarcodes($productRow);
			$this->completeType($productRow);
		}

		return $rows;
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 */
	private function loadProductsData(array $rows): void
	{
		$productIds = array_map(fn($item) => $item->getProductId(), $rows);
		$this->productsInfo = SkuDataProvider::load($productIds);
	}

	private function loadSections(): void
	{
		$sectionIds = [];

		foreach ($this->productsInfo as $productData)
		{
			$sectionIds = array_merge($sectionIds, $productData['SECTION_IDS'] ?? []);
		}

		if (!empty($sectionIds))
		{
			$sort = [];
			$filter = [
				'=ID' => array_unique($sectionIds),
				'ACTIVE' => 'Y',
			];
			$select = ['ID', 'NAME'];
			$rows = \CIBlockSection::GetList($sort, $filter, false, $select);
			while ($row = $rows->Fetch())
			{
				$this->sections[$row['ID']] = [
					'ID' => (int)$row['ID'],
					'NAME' => $row['NAME'],
				];
			}
		}
	}

	private function completeSkuTree(ProductRowViewModel $productRow): void
	{
		$productData = $this->getProductData($productRow);

		$productRow->skuTree = $productData['SKU_TREE'] ?? [];
	}

	private function completeSections(ProductRowViewModel $productRow): void
	{
		$result = [];
		$productData = $this->getProductData($productRow);
		$productSections = $productData['SECTION_IDS'] ?? [];

		foreach ($productSections as $sectionId)
		{
			if ($this->sections[$sectionId])
			{
				$result[] = $this->sections[$sectionId];
			}
		}

		$productRow->sections = $result;
	}

	private function completeGallery(ProductRowViewModel $productRow): void
	{
		$productData = $this->getProductData($productRow);
		$productRow->gallery = [];
		foreach ($productData['GALLERY'] ?? [] as $file)
		{
			$productRow->gallery[] = File::loadWithPreview($file['ID']);
		}
	}

	private function completeBarcodes(ProductRowViewModel $productRow): void
	{
		$productData = $this->getProductData($productRow);
		if (isset($productData['BARCODES']) && is_array($productData['BARCODES']))
		{
			$first = array_shift($productData['BARCODES']);
			if ($first)
			{
				$productRow->barcode = (string)$first['BARCODE'];
			}
		}
	}

	private function completeType(ProductRowViewModel $productRow): void
	{
		$productData = $this->getProductData($productRow);
		if (isset($productData['TYPE']))
		{
			$productRow->type = (int)$productData['TYPE'];
		}
	}

	private function getProductData(ProductRowViewModel $productRow): array
	{
		return $this->productsInfo[$productRow->getProductId()] ?? [];
	}

	private function isPriceEditable(): bool
	{
		$permissions = $this->permissionsProvider->getPermissions();

		/** @var int[] $allowedEntities */
		$allowedEntities = $permissions['catalog_entity_price'];

		return in_array($this->entity->getEntityTypeId(), $allowedEntities, true)
			|| in_array(PermissionDictionary::VALUE_VARIATION_ALL, $allowedEntities, true);
	}

	private function isDiscountEditable(): bool
	{
		$permissions = $this->permissionsProvider->getPermissions();

		/** @var int[] $allowedEntities */
		$allowedEntities = $permissions['catalog_discount'];

		return in_array($this->entity->getEntityTypeId(), $allowedEntities, true)
			|| in_array(PermissionDictionary::VALUE_VARIATION_ALL, $allowedEntities, true);
	}
}
