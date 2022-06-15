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
	 * @param array $rowsIds
	 *
	 * @return array in format `['rowId' => 'basketId']`
	 */
	public function getRowIdsToBasketIdsByRows(array $rowsIds): array
	{
		$row = ProductRowTable::getRow([
			'select' => [
				'ID',
				'OWNER_ID',
				'OWNER_TYPE',
			],
			'filter' => [
				'=ID' => $rowsIds,
				'!OWNER_ID' => null,
				'!OWNER_TYPE' => null,
			],
		]);
		if (!$row)
		{
			return [];
		}

		return $this->getRowIdsToBasketIdsByEntity(
			CCrmOwnerTypeAbbr::ResolveTypeID($row['OWNER_TYPE']),
			$row['OWNER_ID']
		);
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
		$rows = ProductRowTable::getList([
			'select' => [
				'ID',
				'PRODUCT_ID',
				'PRICE',
				'QUANTITY',
			],
			'filter' => [
				'=OWNER_TYPE' => CCrmOwnerTypeAbbr::ResolveByTypeID($ownerTypeId),
				'=OWNER_ID' => $ownerId,
			],
			'order' => [
				'ID' => 'ASC',
			],
		]);
		if (!$rows->getSelectedRowsCount())
		{
			return [];
		}

		$productRelations = new ProductRelationsBuilder();

		foreach ($rows as $row)
		{
			$productRelations->addCrmProductRow(
				$row['ID'],
				$row['PRODUCT_ID'],
				$row['PRICE'],
				$row['QUANTITY'],
			);
		}

		$rows = OrderEntityTable::getList([
			'select' => [
				'ORDER_ID',
			],
			'filter' => [
				'=OWNER_TYPE_ID' => $ownerTypeId,
				'=OWNER_ID' => $ownerId,
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
			],
			'filter' => [
				'=ORDER_ID' => $orderIds,
			],
			'order' => [
				'ID' => 'ASC',
			],
		]);
		if (!$rows->getSelectedRowsCount())
		{
			return [];
		}

		foreach ($rows as $row)
		{
			$productRelations->addSaleBasketItem(
				$row['ID'],
				$row['PRODUCT_ID'],
				$row['PRICE'],
				$row['QUANTITY'],
			);
		}
		return $productRelations->getRelations();
	}


}
