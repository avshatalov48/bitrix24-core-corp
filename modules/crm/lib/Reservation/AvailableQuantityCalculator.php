<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Catalog\StoreProductTable;
use Bitrix\Crm\Reservation\AvailableQuantityCalculator\MissingQuantity;
use Bitrix\Crm\Reservation\AvailableQuantityCalculator\NeedQuantity;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Sale\Reservation\BasketReservationService;

/**
 * An object for calculating the available and missing quantity product rows.
 */
final class AvailableQuantityCalculator
{
	private array $newProductRowProducts = [];
	private array $productRows = [];
	private BasketService $basketService;
	private BasketReservationService $basketReservationService;

	public function __construct()
	{
		$this->basketService = BasketService::getInstance();
		$this->basketReservationService = BasketReservationService::getInstance();
	}

	/**
	 * Add product row to calculator.
	 *
	 * @param int $id for new row, set `0`
	 * @param int $productId
	 * @param int $storeId
	 * @param float $needQuantity
	 *
	 * @return void
	 */
	public function addProductRow(int $id, int $productId, int $storeId, float $needQuantity): void
	{
		if ($productId <= 0)
		{
			return;
		}

		if ($id > 0)
		{
			$this->productRows[$id] = compact('productId', 'storeId', 'needQuantity');
		}
		else
		{
			$this->newProductRowProducts[$productId][$storeId] ??= 0;
			$this->newProductRowProducts[$productId][$storeId] += $needQuantity;
		}

	}

	/**
	 * Missing quantities.
	 *
	 * @return MissingQuantity[]
	 */
	public function getMissingQuantities(): array
	{
		$result = [];

		$needQuantities = $this->getNeedQuantities();
		$availableQuantities = $this->getAvailableQuantities();

		foreach ($needQuantities as $item)
		{
			foreach ($item->getStores() as $storeId => $needQuantity)
			{
				$availableQuantity = $availableQuantities[$item->productId][$storeId] ?? null;
				$missingQuantity =
					isset($availableQuantity)
						? $needQuantity - $availableQuantity
						: $needQuantity
				;

				if ($missingQuantity > 0)
				{
					$result[] = new MissingQuantity(
						$item->id,
						$item->productId,
						$storeId,
						$missingQuantity
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Need quantities.
	 *
	 * @return NeedQuantity[]
	 */
	public function getNeedQuantities(): array
	{
		$result = [];

		foreach ($this->productRows as $id => $data)
		{
			$result[] = new NeedQuantity(
				$id,
				$data['productId'],
				[
					$data['storeId'] => $data['needQuantity'],
				]
			);
		}

		foreach ($this->newProductRowProducts as $productId => $stores)
		{
			$result[] = new NeedQuantity(
				null,
				$productId,
				$stores
			);
		}

		return $result;
	}

	/**
	 * Get available quantity per stores for product rows.
	 *
	 * @return array in format `['productId' => ['storeId' => 'quantity']]
	 */
	public function getAvailableQuantities(): array
	{
		if (empty($this->newProductRowProducts) && empty($this->productRows))
		{
			return [];
		}

		$result = [];

		// first, the quantity from the basket, to account for the history of reserves
		$basketItemsQuantity = $this->getBasketItemsAvailableQuantity($this->productRows);
		if (!empty($basketItemsQuantity))
		{
			$result += $basketItemsQuantity;
		}

		// adding the products rows for which there are no basket items
		$products = $this->newProductRowProducts;
		foreach ($this->productRows as $data)
		{
			$productId = $data['productId'];
			$storeId = $data['storeId'];

			if (!isset($result[$productId][$storeId]))
			{
				$products[$productId][$storeId] ??= 0;
				$products[$productId][$storeId] += $data['needQuantity'];
			}
		}

		$productsQuantity = $this->getProductsAvailableQuantity($products);
		if (!empty($productsQuantity))
		{
			$result += $productsQuantity;
		}

		return $result;
	}

	/**
	 * The available quantity based on the history of reserves of basket items.
	 *
	 * @param array $productRows in format `::productRows` property
	 *
	 * @return array in format `['productId' => ['storeId' => 'quantity']]
	 */
	private function getBasketItemsAvailableQuantity(array $productRows): array
	{
		if (empty($productRows))
		{
			return [];
		}

		$rowIdToBasketId = $this->basketService->getRowIdsToBasketIdsByRows(
			array_keys($productRows)
		);
		if (empty($rowIdToBasketId))
		{
			return [];
		}

		$basketItemAvailableQuantity = $this->basketReservationService->getAvailableCountForBasketItems([
			'=ID' => $rowIdToBasketId,
		]);
		if (empty($basketItemAvailableQuantity))
		{
			return [];
		}

		$result = [];
		foreach ($rowIdToBasketId as $rowId => $basketId)
		{
			if (isset($basketItemAvailableQuantity[$basketId]))
			{
				$row = $productRows[$rowId] ?? null;
				if ($row)
				{
					$productId = $row['productId'];
					$result[$productId] = $basketItemAvailableQuantity[$basketId];
				}
			}
		}

		return $result;
	}

	/**
	 * The available quantity based on the remaining items in stock.
	 *
	 * @param array $products in format `::newProductRowProducts` property
	 *
	 * @return array in format `['productId' => ['storeId' => 'quantity']]
	 */
	private function getProductsAvailableQuantity(array $products): array
	{
		if (empty($products))
		{
			return [];
		}

		$storeIds = [];
		$productIds = [];
		foreach ($products as $productId => $stores)
		{
			$productIds[] = $productId;
			foreach ($stores as $storeId => $value)
			{
				$storeIds[] = $storeId;
			}
		}

		$result = [];

		$rows = StoreProductTable::getList([
			'select' => [
				'PRODUCT_ID',
				'AMOUNT',
				'STORE_ID',
				'QUANTITY_RESERVED',
			],
			'filter' => [
				'=PRODUCT_ID' => $productIds,
				'=STORE_ID' => $storeIds,
			],
		]);
		foreach ($rows as $row)
		{
			$storeId = (int)$row['STORE_ID'];
			$productId = (int)$row['PRODUCT_ID'];

			$result[$productId][$storeId] = (float)($row['AMOUNT'] - $row['QUANTITY_RESERVED']);
		}

		return $result;
	}
}
