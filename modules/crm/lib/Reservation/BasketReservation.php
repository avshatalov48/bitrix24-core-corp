<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Main;
use Bitrix\Sale;

class BasketReservation
{
	private $products = [];

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
		$this->products[$product['ID']] = $product;
	}

	public function addProducts(array $products): void
	{
		foreach ($products as $product)
		{
			$this->addProduct($product);
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
						'RESERVE_ID' => $basketReservation['ID'],
						'STORE_ID' => $basketReservation['STORE_ID'],
						'RESERVE_QUANTITY' => $basketReservation['QUANTITY'],
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

		if (!$this->products)
		{
			return $result;
		}

		$productIds = array_column($this->products, 'ID');

		$productReservationMapIterator = Internals\ProductReservationMapTable::getList([
			'select' => ['PRODUCT_ROW_ID', 'BASKET_RESERVATION_ID'],
			'filter' => [
				'=PRODUCT_ROW_ID' => $productIds,
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