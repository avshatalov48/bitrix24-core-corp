<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products;

use Bitrix\Crm\Order\ProductManager\EntityProductConverter;
use Bitrix\Crm\Service\Sale\Basket\ProductRelationsBuilder;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Result;
use CApplicationException;
use CCrmDeal;
use CMain;
use Bitrix\Catalog;

Loader::requireModule('sale');

/**
 * Synchronize deal products with the basket items.
 * Main entity for that synchronizer - basket (order).
 *
 * For example:
 * ```php
 * $order = \Bitrix\Crm\Order\Order::load($orderId);
 * $dealProductRows = \Bitrix\Crm\ProductRowTable::getList([
 *     'filter' => [
 *         '=OWNER_TYPE' => CCrmOwnerTypeAbbr::Deal,
 *         '=OWNER_ID' => $dealId,
 *     ],
 * ])->fetchAll();
 *
 * $synchronizer = new \Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketItemsSynchronizer($order->getBasket(), $dealProductRows);
 * $resultProductRows = $synchronizer->sync();
 * $result = $synchronizer->syncAndSave($dealId);
 * ```
 */
class BasketItemsSynchronizer
{
	private Basket $basket;
	private array $productRows;

	/**
	 * @param Basket $basket
	 * @param array $productRows
	 */
	public function __construct(Basket $basket, array $productRows)
	{
		$this->basket = $basket;
		$this->productRows = $productRows;
	}

	/**
	 * Synchronize product rows with basket items.
	 *
	 * If the basket item contains 'XML_ID' with 'ROW_ID`, this binds it to a row.
	 * If the basket item is new, it is skipped because it cannot be linked with the product row.
	 *
	 * @return array actual product rows.
	 */
	public function sync(): array
	{
		$converter = new EntityProductConverter();
		$converter->setBasketItem($this->basket);

		$existRows = $this->getRelationsProductRows();

		$result = [];
		foreach ($this->basket as $basketItem)
		{
			/**
			 * @var BasketItem $basketItem
			 */

			$basketId = $basketItem->getId();
			$row = $converter->convertToCrmProductRowFormat($basketItem->getFieldValues());

			$existRow = $existRows[$basketId] ?? null;
			if ($existRow)
			{
				$row = array_merge($existRow, [
					'PRODUCT_ID' => $row['PRODUCT_ID'],
					'PRICE' => $row['PRICE'],
					'PRICE_EXCLUSIVE' => $row['PRICE_EXCLUSIVE'],
					'PRICE_NETTO' => $row['PRICE_NETTO'],
					'PRICE_BRUTTO' => $row['PRICE_BRUTTO'],
					'DISCOUNT_SUM' => $row['DISCOUNT_SUM'],
					'DISCOUNT_RATE' => $row['DISCOUNT_RATE'],
					'DISCOUNT_TYPE_ID' => $row['DISCOUNT_TYPE_ID'],
					'QUANTITY' => $row['QUANTITY'],
					'TAX_RATE' => $row['TAX_RATE'],
					'TAX_INCLUDED' => $row['TAX_INCLUDED'],
				]);
			}

			$basketXmlId = (string)$basketItem->getField('XML_ID');
			$rowId = $basketXmlId ? BasketXmlId::getRowIdFromXmlId($basketXmlId) : null;
			if ($rowId)
			{
				$row['ID'] = $rowId;
			}

			if (!isset($row['XML_ID']) && $basketId)
			{
				$row['XML_ID'] = ProductRowXmlId::getXmlIdFromBasketId($basketId);
			}

			$result[] = $row;
		}

		return $result;
	}

	/**
	 * Synchronize product rows with basket items, and save deal.
	 *
	 * @param int $dealId
	 *
	 * @return Result
	 */
	public function syncAndSave(int $dealId): Result
	{
		global $APPLICATION;

		/**
		 * @var CMain $APPLICATION
		 */

		$result = new Result();
		$dealProducts = $this->sync();

		$ret = CCrmDeal::SaveProductRows($dealId, $dealProducts, false, true, true);
		if ($ret === false)
		{
			$e = $APPLICATION->GetException();
			if ($e instanceof CApplicationException)
			{
				$result->addError(
					new Error($e->GetString())
				);
			}
		}

		return $result;
	}

	/**
	 * Get mapped array by `BASKET_ID` product rows.
	 *
	 * @return array in format `[basketId => row]`
	 */
	private function getRelationsProductRows(): array
	{
		$productRelationsBuilder = new ProductRelationsBuilder();

		foreach ($this->productRows as $row)
		{
			if (isset($row['ID']))
			{
				$productRelationsBuilder->addCrmProductRow(
					(int)$row['ID'],
					(int)$row['PRODUCT_ID'],
					(float)$row['PRICE'],
					(float)$row['QUANTITY'],
					(string)$row['XML_ID']
				);
			}
		}

		foreach ($this->basket as $basketItem)
		{
			/**
			 * @var BasketItem $basketItem
			 */

			if ($basketItem->getId())
			{
				$productRelationsBuilder->addSaleBasketItem(
					$basketItem->getId(),
					$basketItem->getProductId(),
					$basketItem->getPrice(),
					$basketItem->getQuantity(),
					(string)$basketItem->getField('XML_ID')
				);
			}
		}

		$result = [];
		$relations = $productRelationsBuilder->getRelations();

		foreach ($this->productRows as $row)
		{
			if (!isset($row['ID']))
			{
				continue;
			}

			$rowId = (int)$row['ID'];
			$basketId = $relations[$rowId] ?? null;
			if ($basketId)
			{
				$result[$basketId] = $row;
			}
		}

		return $result;
	}
}
