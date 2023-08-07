<?php

namespace Bitrix\Crm\Service\Sale;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Service\Sale\Basket\ProductRelationsBuilder;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\BasketTable;
use CCrmOwnerTypeAbbr;

/**
 * Service for working with basket items.
 */
class BasketService
{
	public function __construct()
	{
		Loader::includeModule('sale');
	}

	/**
	 * Service instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		return ServiceLocator::getInstance()->get('crm.basket');
	}

	/**
	 * Get relations sale basket items and crm product rows.
	 *
	 * @param array $filter for tablet `ProductRowTable`.
	 *
	 * @return array in format `['rowId' => 'basketId']`
	 */
	private function getRowsIdsToBasketIdsByFilter(array $filter): array
	{
		$rows = ProductRowTable::getList([
			'select' => [
				'ID',
				'OWNER_ID',
				'OWNER_TYPE',
				'PRODUCT_ID',
				'PRICE',
				'QUANTITY',
				'XML_ID',
			],
			'filter' => $filter,
			'order' => [
				'ID' => 'ASC',
			],
		]);
		if ($rows->getSelectedRowsCount() === 0)
		{
			return [];
		}

		$productRelations = new ProductRelationsBuilder();
		$orderFilters = [];

		foreach ($rows as $row)
		{
			$productRelations->addCrmProductRow(
				(int)$row['ID'],
				(int)$row['PRODUCT_ID'],
				(float)$row['PRICE'],
				(float)$row['QUANTITY'],
				(string)$row['XML_ID']
			);

			$key = $row['OWNER_TYPE'] . $row['OWNER_ID'];
			if (empty($orderFilters[$key]))
			{
				$orderFilters[$key] = [
					'=OWNER_TYPE_ID' => CCrmOwnerTypeAbbr::ResolveTypeID($row['OWNER_TYPE']),
					'=OWNER_ID' => (int)$row['OWNER_ID'],
				];
			}
		}

		if (empty($orderFilters))
		{
			return [];
		}
		$rows = OrderEntityTable::getList([
			'select' => [
				'ORDER_ID',
			],
			'filter' => [
				'LOGIC' => 'OR',
				...array_values($orderFilters),
			],
		]);

		$orderIds = array_column($rows->fetchAll(), 'ORDER_ID');
		if (empty($orderIds))
		{
			return [];
		}

		$rows = BasketTable::getList([
			'select' => [
				'ID',
				'PRODUCT_ID',
				'QUANTITY',
				'PRICE',
				'XML_ID',
			],
			'filter' => [
				'=ORDER_ID' => $orderIds,
			],
			'order' => [
				'ID' => 'ASC',
			],
		]);
		if ($rows->getSelectedRowsCount() === 0)
		{
			return [];
		}

		foreach ($rows as $row)
		{
			$productRelations->addSaleBasketItem(
				(int)$row['ID'],
				(int)$row['PRODUCT_ID'],
				(float)$row['PRICE'],
				(float)$row['QUANTITY'],
				(string)$row['XML_ID']
			);
		}
		return $productRelations->getRelations();
	}

	/**
	 * Get relations sale basket items and crm product rows.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 *
	 * @return array in format `['rowId' => 'basketId']`
	 */
	public function getRowIdsToBasketIdsByEntity(int $ownerTypeId, int $ownerId): array
	{
		return $this->getRowsIdsToBasketIdsByFilter([
			'=OWNER_TYPE' => CCrmOwnerTypeAbbr::ResolveByTypeID($ownerTypeId),
			'=OWNER_ID' => $ownerId,
		]);
	}

	/**
	 * Get relations sale basket items and crm product rows.
	 *
	 * @param array $rowsIds
	 *
	 * @return array in format `['rowId' => 'basketId']`
	 */
	public function getRowIdsToBasketIdsByRows(array $rowsIds): array
	{
		return $this->getRowsIdsToBasketIdsByFilter([
			'=ID' => $rowsIds,
		]);
	}
}
