<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products;

use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderDealSynchronizer\SynchronizeException;
use Bitrix\Crm\Order\ProductManager\EntityProductConverter;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Service\Sale\Basket\ProductRelationsBuilder;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sale\BasketItem;

/**
 * Synchronize order basket with deal product rows.
 * Main entity for that synchronizer - deal.
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
 * $synchronizer = new \Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowSynchronizer($order, $dealProductRows);
 * $result = $synchronizer->syncAndSave($withVerification);
 * ```
 */
class ProductRowSynchronizer
{
	/**
	 * @var Order
	 */
	private Order $order;

	/**
	 * Product rows of deal.
	 * Struct must be equivalent table `b_crm_product_row`.
	 *
	 * @var array
	 */
	private array $productRows;

	/**
	 * Throw `SynchronizeException` with result errors.
	 *
	 * @param Result $result
	 *
	 * @return never
	 * @throws SynchronizeException
	 */
	private static function throwSyncExceptionByResult(Result $result): void
	{
		throw new SynchronizeException(
			join(', ', $result->getErrorMessages())
		);
	}

	/**
	 * @param Order $order
	 * @param array $productRows
	 */
	public function __construct(Order $order, array $productRows)
	{
		$this->order = $order;
		$this->productRows = $productRows;
	}

	/**
	 * Verification the synchronization result and the order after synchronization.
	 *
	 * @return Result
	 */
	public function verify(): Result
	{
		try
		{
			$this->sync(true);
		}
		catch (SynchronizeException $e)
		{
			$result = new Result();
			$result->addError(
				new Error($e->getMessage())
			);
			return $result;
		}

		return $this->order->verify();
	}

	/**
	 * Synchronize basket items with product rows.
	 *
	 * If deal product is new (not contains `ID`), then throw `SynchronizeException`,
	 * because new product row cannot linked with the basket item.
	 * If not found needed basket item by **product row field** `XML_ID` (aka basket item id),
	 * then it searching basket item by **basket item field** `XML_ID` (aka product row id with prefix).
	 * If deal product row not exists in basket, then it is created.
	 * If basket item not exists in product rows, then it is deleted.
	 *
	 * @param bool $withVerification if TRUE, then all changes to the basket (setting values, deleting an item, etc.)
	 * are processed with verification of the correctness of the operation.
	 *
	 * @return array new basket items in format `['rowId' => 'basketItem']`.
	 * @throws SynchronizeException
	 */
	public function sync(bool $withVerification): array
	{
		$basket = $this->order->getBasket();
		if (!$basket)
		{
			return [];
		}

		$converter = new EntityProductConverter;

		$newProductRows = [];
		$usedBasketCodes = [];
		$productRowIdToBasketId = $this->getRelationsProductRows();

		foreach ($this->productRows as $dealProduct)
		{
			$rowId = (int)($dealProduct['ID'] ?? 0);
			if (!$rowId)
			{
				// NOT with verification, because you need to interrupt the process only in case of real actions.
				if (!$withVerification)
				{
					throw new SynchronizeException("Synchronizer work with only saved product rows.");
				}
			}

			$dealBasketItem = $converter->convertToSaleBasketFormat($dealProduct);

			$quantity = (float)$dealBasketItem['QUANTITY'];
			if ($quantity === 0.0)
			{
				continue;
			}

			$basketId = $productRowIdToBasketId[$rowId] ?? null;
			$basketItem = $basket->getItemById($basketId);
			if (!$basketItem)
			{
				$dealBasketItem['CURRENCY'] = $this->order->getCurrency();
				$dealBasketItem['XML_ID'] = BasketXmlId::getXmlIdFromRowId($rowId);

				// for custom products - not setted provider
				$productId = (int)$dealBasketItem['PRODUCT_ID'];
				if ($productId > 0)
				{
					$dealBasketItem['PRODUCT_PROVIDER_CLASS'] = CatalogProvider::class;
				}

				$basketItem = $basket->createItem('catalog', $dealBasketItem['PRODUCT_ID']);

				$newProductRows[$rowId] = $basketItem;
			}

			unset(
				$dealBasketItem['MODULE'],
			);
			$dealBasketItem = $this->clearBasketItemExtraFields($dealBasketItem);

			$result = $basketItem->setFields($dealBasketItem);
			if (!$result->isSuccess())
			{
				if ($withVerification)
				{
					self::throwSyncExceptionByResult($result);
				}
				else
				{
					foreach ($dealBasketItem as $name => $value)
					{
						$result = $basketItem->setField($name, $value);
						if (!$result->isSuccess())
						{
							$basketItem->setFieldNoDemand($name, $value);
						}
					}
				}
			}

			$usedBasketCodes[] = $basketItem->getBasketCode();
		}

		foreach ($basket as $basketItem)
		{
			/**
			 * @var BasketItem $basketItem
			 */

			if (!in_array($basketItem->getBasketCode(), $usedBasketCodes, true))
			{
				$result = $basketItem->delete();
				if ($withVerification && !$result->isSuccess())
				{
					self::throwSyncExceptionByResult($result);
				}
			}
		}

		return $newProductRows;
	}

	/**
	 * Synchronize basket items with product rows, and save order.
	 *
	 * @param bool $withVerification
	 *
	 * @return Result
	 */
	public function syncAndSave(bool $withVerification): Result
	{
		$newProductRows = $this->sync($withVerification);

		$this->order->doFinalAction(true);

		$result = $this->order->save();
		if ($result->isSuccess())
		{
			foreach ($newProductRows as $rowId => $basketItem)
			{
				ProductRowTable::update($rowId, [
					'XML_ID' => ProductRowXmlId::getXmlIdFromBasketId($basketItem->getId()),
				]);
			}
		}

		return $result;
	}

	/**
	 * Deleting fields that are not available for the basket item.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	private function clearBasketItemExtraFields(array $fields): array
	{
		$availableFields = BasketItem::getAllFields();

		return array_filter(
			$fields,
			fn($key) => in_array($key, $availableFields),
			ARRAY_FILTER_USE_KEY
		);
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

		foreach ($this->order->getBasket() as $basketItem)
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

		return $productRelationsBuilder->getRelations();
	}
}
