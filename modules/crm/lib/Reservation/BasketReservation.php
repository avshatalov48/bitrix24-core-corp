<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Main;
use Bitrix\Sale;

final class BasketReservation
{
	private array $productsRowsIds = [];

	/**
	 * @throws Main\SystemException
	 */
	public function __construct()
	{
		if (!Main\Loader::includeModule('sale'))
		{
			throw new Main\SystemException('Sale not installed');
		}
	}

	public function addProduct(array $product): void
	{
		$this->productsRowsIds[] = (int)$product['ID'];
	}

	public function addProducts(array $products): void
	{
		foreach ($products as $product)
		{
			if (is_array($product))
			{
				$this->addProduct($product);
			}
		}
	}

	public function getReservedProducts(): array
	{
		$result = [];

		$productReservationMap = $this->getReservationMap();
		if ($productReservationMap)
		{
			$reservationProductMap = array_flip($productReservationMap);

			$basketReservationIterator = Sale\ReserveQuantityCollection::getList([
				'select' => ['ID', 'QUANTITY', 'STORE_ID', 'DATE_RESERVE_END'],
				'filter' => [
					'=ID' => array_values($productReservationMap),
				],
			]);
			while ($basketReservation = $basketReservationIterator->fetch())
			{
				$productRowId = $reservationProductMap[$basketReservation['ID']] ?? null;
				if ($productRowId)
				{
					$result[$productRowId] = [
						'RESERVE_ID' => (int)$basketReservation['ID'],
						'STORE_ID' => (int)$basketReservation['STORE_ID'],
						'RESERVE_QUANTITY' => (float)$basketReservation['QUANTITY'],
					];
					if ($basketReservation['DATE_RESERVE_END'] instanceof Main\Type\Date)
					{
						$result[$productRowId]['DATE_RESERVE_END'] =
							$basketReservation['DATE_RESERVE_END']->format(Main\Type\Date::convertFormatToPhp(FORMAT_DATE))
						;
					}
				}
			}
		}

		return $result;
	}

	public function getReservationMap(): array
	{
		$result = [];

		if (empty($this->productsRowsIds))
		{
			return $result;
		}

		$productReservationMapIterator = Internals\ProductReservationMapTable::getList([
			'select' => ['PRODUCT_ROW_ID', 'BASKET_RESERVATION_ID'],
			'filter' => [
				'=PRODUCT_ROW_ID' => $this->productsRowsIds,
			]
		]);
		while ($productReservationMapData = $productReservationMapIterator->fetch())
		{
			$productRowId = (int)$productReservationMapData['PRODUCT_ROW_ID'];
			$basketReservationId = (int)$productReservationMapData['BASKET_RESERVATION_ID'];

			$result[$productRowId] = $basketReservationId;
		}

		return $result;
	}
}
