<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products;

use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowSynchronizer\BasketItemFiller;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowSynchronizer\SyncResult;
use Bitrix\Crm\Order\OrderDealSynchronizer\SynchronizeException;
use Bitrix\Crm\Order\ProductManager\EntityProductConverter;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Service\Sale\Basket\ProductRelationsBuilder;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sale\Basket\RefreshFactory;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

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
class ProductRowSynchronizer implements LoggerAwareInterface
{
	use LoggerAwareTrait;

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

		$this->logger = Logger::create('crm.orderdealsynchronizer.productrowsynchronizer') ?? new NullLogger();
	}

	/**
	 * Log debug information with auto added order info.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	private function logDebug(string $message): void
	{
		$this->logger->debug("[{$this->order->getField('XML_ID')}|{$this->order->getId()}] - {$message}\n");
	}

	/**
	 * Verification the synchronization result and the order after synchronization.
	 *
	 * @return Result
	 */
	public function verify(): Result
	{
		$this->logDebug("verify: start");

		try
		{
			$this->sync(true);
		}
		catch (SynchronizeException $e)
		{
			$this->logDebug('verify: end, error: ' . $e->getMessage());

			$result = new Result();
			$result->addError(
				new Error($e->getMessage())
			);
			return $result;
		}

		$this->logDebug('verify: end, success');

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
	 * @return SyncResult
	 *
	 * @throws SynchronizeException
	 */
	public function sync(bool $withVerification): SyncResult
	{
		$this->logDebug('sync: start, verification: ' . ($withVerification ? 1 : 0));

		$syncResult = new SyncResult();

		$basket = $this->order->getBasket();
		if (!$basket)
		{
			$this->logDebug('sync: skip, without basket');

			return $syncResult;
		}

		if ($basket->isEmpty() && !$basket->getFUserId(true))
		{
			$this->fillBasketFuserFromOrder($basket);
		}

		$converter = new EntityProductConverter;

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
				// skip only during verification.
				if ($withVerification)
				{
					continue;
				}
			}

			$basketId = $productRowIdToBasketId[$rowId] ?? null;
			$basketItem = $basket->getItemById($basketId);
			if (!$basketItem)
			{
				$dealBasketItem['CURRENCY'] = $this->order->getCurrency();
				$dealBasketItem['XML_ID'] = BasketXmlId::getXmlIdFromRowId($rowId);

				// for custom products - does not set the provider.
				$productId = (int)$dealBasketItem['PRODUCT_ID'];
				if ($productId > 0)
				{
					$dealBasketItem['PRODUCT_PROVIDER_CLASS'] = '\\'.CatalogProvider::class;
				}

				$moduleId = $productId > 0 ? 'catalog' : '';
				$basketItem = $basket->createItem($moduleId, $dealBasketItem['PRODUCT_ID']);
				$syncResult->addNewBasketItem($rowId, $basketItem);
			}

			$filler = new BasketItemFiller($basketItem);
			$filler->fill($dealBasketItem);
			if ($filler->getChanged())
			{
				$result = $filler->getResult();
				if (!$result->isSuccess() && $withVerification)
				{
					self::throwSyncExceptionByResult($result);
				}
				$syncResult->markChanged();
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

				$syncResult->markChanged();
			}
		}

		$changed = $syncResult->getChanged() ? 1 : 0;
		$count = count($syncResult->getNewBasketItems());
		$this->logDebug("sync: finish, changed: {$changed}, new basket items: {$count}");

		return $syncResult;
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
		$this->logDebug('syncAndSave: start');

		$syncResult = $this->sync($withVerification);
		if (!$syncResult->getChanged())
		{
			$this->logDebug('syncAndSave: skip, no changed');

			return new Result();
		}

		/** @var \Bitrix\Crm\Order\BasketItem $basketItem */
		foreach ($this->order->getBasket() as $basketItem)
		{
			if (
				$basketItem->getId() === 0
				|| $basketItem->getFields()->isChanged('PRODUCT_ID')
			)
			{
				$strategy = RefreshFactory::createSingle($basketItem->getBasketCode());
				$this->order->getBasket()->refresh($strategy);
			}
		}

		$this->order->doFinalAction(true);

		$result = $this->order->save();
		if ($result->isSuccess())
		{
			foreach ($syncResult->getNewBasketItems() as $rowId => $basketItem)
			{
				ProductRowTable::update($rowId, [
					'XML_ID' => ProductRowXmlId::getXmlIdFromBasketId($basketItem->getId()),
				]);
			}
		}

		$status = $result->isSuccess() ? 1 : 0;
		if (!$status)
		{
			$status .= ", errors: " . join(', ', $result->getErrorMessages());
		}
		$this->logDebug("syncAndSave: save order, result: {$status}");

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
					(string) ($row['XML_ID'] ?? null)
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

	/**
	 * Fill fuser of basket from order user id.
	 *
	 * @param BasketBase $basket
	 *
	 * @return void
	 */
	private function fillBasketFuserFromOrder(BasketBase $basket): void
	{
		$orderUserId = (int)$this->order->getUserId();
		if ($orderUserId > 0)
		{
			$fuserId = Fuser::getIdByUserId($orderUserId);
			if ($fuserId > 0)
			{
				$basket->setFUserId($fuserId);
			}
		}
	}
}
