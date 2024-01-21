<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\CrmMobile\ProductGrid\ProductRowViewModel;
use Bitrix\CrmMobile\ProductGrid\StoreDataProvider;

final class CompleteStores implements EnricherContract
{
	private Item $entity;
	private array $storeData = [];

	public function __construct(Item $entity)
	{
		$this->entity = $entity;
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 * @return ProductRowViewModel[]
	 */
	public function enrich(array $rows): array
	{
		if (!\CCrmSaleHelper::isAllowedReservation(
			$this->entity->getEntityTypeId(),
			$this->entity->getCategoryId() ?? 0
		))
		{
			return $rows;
		}

		$this->storeData = StoreDataProvider::provideStoreData(
			array_map(
				static function (ProductRowViewModel $row) {
					return $row->source->getProductId();
				},
				$rows
			)
		);

		foreach ($rows as $productRow)
		{
			$sourceMutations = $this->getSourceMutations($productRow);
			if ($sourceMutations)
			{
				$productRow->source = $this->rebuildSource($productRow->source, $sourceMutations);
			}

			$storeId = $productRow->source->toArray()['STORE_ID'] ?? 0;

			$productRow->stores = $this->getStores($productRow);
			$productRow->hasStoreAccess = !$storeId || $this->hasStoreAccess($storeId);
			$productRow->storeName = $this->getStoreName($productRow);
			$productRow->storeAmount = $this->getStoreAmount($productRow);
			$productRow->storeAvailableAmount = $this->getStoreAvailableAmount($productRow);
			$productRow->rowReserved = $this->getRowReserved($productRow);
			$productRow->deductedQuantity = $this->getDeductedQuantity($productRow);
			$productRow->inputReserveQuantity = $this->getInputReserveQuantity($productRow);
			$productRow->shouldSyncReserveQuantity = $this->shouldSyncReserveQuantity($productRow);
		}

		return $rows;
	}

	private function getSourceMutations(ProductRowViewModel $productRow): array
	{
		if ($productRow->source->isNew())
		{
			$productId = $productRow->getProductId();

			return [
				'STORE_ID' => $this->storeData[$productId]['INITIAL_STORE']['ID'] ?? null,
			];
		}

		return [];
	}

	private function getStores(ProductRowViewModel $productRow): array
	{
		$productId = $productRow->getProductId();
		$stores =
			(
				isset($this->storeData[$productId]['STORES'])
				&& is_array($this->storeData[$productId]['STORES'])
			)
				? $this->storeData[$productId]['STORES']
				: []
		;

		return array_values($stores);
	}

	private function getStoreName(ProductRowViewModel $productRow): ?string
	{
		$source = $productRow->source->toArray();

		$storeId = isset($source['STORE_ID']) ? (int)$source['STORE_ID'] : 0;
		if ($storeId && !$this->hasStoreAccess($storeId))
		{
			return null;
		}

		if ($storeId)
		{
			$store = StoreTable::getById($storeId)->fetch();
			if ($store)
			{
				return (string)$store['TITLE'];
			}
		}

		return null;
	}

	private function getStoreInfo(ProductRowViewModel $productRow): ?array
	{
		$source = $productRow->source->toArray();

		$storeId = isset($source['STORE_ID']) ? (int)$source['STORE_ID'] : 0;
		if ($storeId && !$this->hasStoreAccess($storeId))
		{
			return null;
		}

		$productId = $productRow->getProductId();

		return $this->storeData[$productId]['STORES'][$storeId] ?? null;
	}

	private function getStoreAmount(ProductRowViewModel $productRow): float
	{
		$storeInfo = $this->getStoreInfo($productRow);

		return $storeInfo ? (float)$storeInfo['AMOUNT'] : 0;
	}

	private function getStoreAvailableAmount(ProductRowViewModel $productRow): float
	{
		$storeInfo = $this->getStoreInfo($productRow);

		return
			$storeInfo
				? (float)$storeInfo['AMOUNT'] - (float)$storeInfo['QUANTITY_RESERVED']
				: 0
			;
	}

	private function getRowReserved(ProductRowViewModel $productRow): float
	{
		if ($productRow->source->isNew())
		{
			return 0;
		}

		$source = $productRow->source->toArray();

		$rows = ReservationService::getInstance()->fillBasketReserves([$source]);
		if (!isset($rows[0]))
		{
			return 0;
		}

		$row = $rows[0];

		return isset($row['RESERVE_QUANTITY']) ? (float)$row['RESERVE_QUANTITY'] : 0;
	}

	private function getDeductedQuantity(ProductRowViewModel $productRow): float
	{
		if ($productRow->source->isNew())
		{
			return 0;
		}

		$source = $productRow->source->toArray();
		$id = isset($source['ID']) ? (int)$source['ID'] : 0;
		$productId = isset($source['PRODUCT_ID']) ? (int)$source['PRODUCT_ID'] : 0;

		if (!$id || !$productId)
		{
			return 0;
		}

		$shippedRowMap = Container::getInstance()->getShipmentProductService()->getShippedQuantityByEntity(
			(string)\CCrmOwnerType::ResolveID($source['OWNER_TYPE']),
			$source['OWNER_ID']
		);

		return isset($shippedRowMap[$id]) ? (float)$shippedRowMap[$id] : 0;
	}

	private function getInputReserveQuantity(ProductRowViewModel $productRow): float
	{
		if ($productRow->source->isNew())
		{
			if (!$this->shouldSyncReserveQuantity($productRow))
			{
				return 0;
			}

			$source = $productRow->source->toArray();

			return isset($source['QUANTITY']) ? (float)$source['QUANTITY'] : 0;
		}

		$source = $productRow->source->toArray();

		return isset($source['RESERVE_QUANTITY']) ? (float)$source['RESERVE_QUANTITY'] : 0;
	}

	private function shouldSyncReserveQuantity(ProductRowViewModel $productRow): bool
	{
		if ($productRow->source->isNew())
		{
			return ReservationService::getInstance()->isReserveEqualProductQuantity();
		}

		$source = $productRow->source->toArray();

		$reserveQuantity = isset($source['RESERVE_QUANTITY']) ? (float)$source['RESERVE_QUANTITY'] : 0;

		return (
			$source['QUANTITY'] === $reserveQuantity
			&& ReservationService::getInstance()->isReserveEqualProductQuantity()
		);
	}

	private function hasStoreAccess(int $storeId): bool
	{
		return AccessController::getCurrent()->checkByValue(
			ActionDictionary::ACTION_STORE_VIEW,
			(string)$storeId
		);
	}

	private function rebuildSource(ProductRow $source, array $mutations): ProductRow
	{
		$result = ProductRow::createFromArray(array_merge(
			$source->toArray(),
			$mutations
		));
		$this->entity->addToProductRows($result);

		return $result;
	}
}
