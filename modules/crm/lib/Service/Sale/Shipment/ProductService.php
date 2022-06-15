<?php

namespace Bitrix\Crm\Service\Sale\Shipment;

use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Sale\Internals\ShipmentItemTable;

/**
 * Service for working with product of shipment.
 */
class ProductService
{
	/**
	 * @var BasketService
	 */
	private $basketService;

	/**
	 * @param BasketService $basketService
	 */
	public function __construct(
		BasketService $basketService
	)
	{
		Loader::includeModule('sale');

		$this->basketService = $basketService;
	}

	/**
	 * Get products added in shipments with quantity.
	 *
	 * @param string $ownerTypeId
	 * @param int $ownerId
	 * @param bool $onlyDeducted if TRUE - return only deducted rows
	 *
	 * @return array in format `['rowId' => 'quantity']`
	 */
	public function getShippedQuantityByEntity(string $ownerTypeId, int $ownerId, bool $onlyDeducted = true): array
	{
		return $this->getShippedQuantityByRowBasketMap(
			$this->basketService->getRowIdsToBasketIdsByEntity($ownerTypeId, $ownerId),
			$onlyDeducted
		);
	}

	/**
	 * Get products added in shipments with quantity.
	 *
	 * @param array $productRow2basket in format `[rowId => basketId]`
	 * @param bool $onlyDeducted
	 *
	 * @return array in format `[rowId => shippedQuantity]
	 */
	public function getShippedQuantityByRowBasketMap(array $productRow2basket, bool $onlyDeducted = true): array
	{
		$result = [];

		$filter = [
			'=BASKET_ID' => array_values($productRow2basket),
		];
		if ($onlyDeducted)
		{
			$filter['=DELIVERY.DEDUCTED'] = 'Y';
		}

		$rows = ShipmentItemTable::getList([
			'select' => [
				'BASKET_ID',
				'SUM_QUANTITY',
			],
			'filter' => $filter,
			'runtime' => [
				new ExpressionField('SUM_QUANTITY', 'SUM(%s)', 'QUANTITY'),
			],
			'group' => [
				'BASKET_ID',
			],
		]);
		$basketIdToQuantity = array_column($rows->fetchAll(), 'SUM_QUANTITY', 'BASKET_ID');

		foreach ($productRow2basket as $rowId => $basketId)
		{
			$result[$rowId] = (float)($basketIdToQuantity[$basketId] ?? 0.0);
		}
		return $result;
	}
}