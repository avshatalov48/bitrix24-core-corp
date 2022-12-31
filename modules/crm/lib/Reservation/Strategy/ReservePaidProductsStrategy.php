<?php

namespace Bitrix\Crm\Reservation\Strategy;

use Bitrix\Crm\Order\BasketItem;
use Bitrix\Crm\Order\EntityBinding;
use Bitrix\Crm\Order\PayableBasketItem;
use Bitrix\Crm\Order\PayableItemCollection;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Crm\Service\Sale\Reservation\ShipmentService;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\Date;
use Bitrix\Sale\Registry;
use CCrmOwnerTypeAbbr;

/**
 * Strategy automatic reservation of product rows when they are paid.
 *
 * If the product is already reserved for an equal or greater quantity, then nothing happens.
 * If the product is already reserved for a smaller quantity, the reserve is updated to the paid quantity.
 * If the product has not been reserved yet, a new reserve is created for the paid quantity.
 */
class ReservePaidProductsStrategy extends ManualStrategy
{
	public int $defaultStoreId;
	public ?Date $defaultDateReserveEnd;

	public function __construct()
	{
		Loader::requireModule('sale');
	}

	/**
	 * @inheritDoc
	 */
	public function reservation(int $ownerTypeId, int $ownerId): ReservationResult
	{
		$result = new ReservationResult();

		$paidRows = $this->getPaidProductRows($ownerTypeId, $ownerId);

		$rowIds = array_keys($paidRows);
		$existReserves = $this->getReservesByRowsIds($rowIds);
		$deductedRows = $this->getDeductedProductRows($rowIds);
		foreach ($paidRows as $rowId => $paidQuantity)
		{
			$reserve = $existReserves[$rowId] ?? null;
			$reserveQuantity = (float)($reserve['RESERVE_QUANTITY'] ?? 0);
			$deductedQuantity = (float)($deductedRows[$rowId] ?? 0.0);
			$newReserveQuantity = $paidQuantity - $deductedQuantity;

			if ($reserveQuantity >= $newReserveQuantity)
			{
				continue;
			}

			$reserveInfo = $result->addReserveInfo(
				$rowId,
				$newReserveQuantity,
				$newReserveQuantity - $reserveQuantity
			);

			if ($reserve)
			{
				$reserveInfo->setStoreId($reserve['STORE_ID']);
				$reserveInfo->setDateReserveEnd((string)$reserve['DATE_RESERVE_END']);

				$saveResult = $this->saveCrmReserve([
					'ID' => $reserve['ID'],
					'RESERVE_QUANTITY' => $newReserveQuantity,
				]);
			}
			else
			{
				$reserveInfo->setStoreId($this->defaultStoreId);
				$reserveInfo->setDateReserveEnd((string)$this->defaultDateReserveEnd);

				$saveResult = $this->saveCrmReserve([
					'ROW_ID' => $rowId,
					'RESERVE_QUANTITY' => $newReserveQuantity,
					'STORE_ID' => $this->defaultStoreId,
					'DATE_RESERVE_END' => $this->defaultDateReserveEnd,
				]);
			}

			$result->addErrors($saveResult->getErrors());
		}

		return $result;
	}

	/**
	 * Removing reserves for payment items.
	 *
	 * Used when canceling a payment.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @param Payment $payment
	 *
	 * @return ReservationResult
	 */
	public function removeReservesPaymentProducts(int $ownerTypeId, int $ownerId, Payment $payment): ReservationResult
	{
		$result = new ReservationResult();

		/**
		 * @var PayableBasketItem[] $paymentBasketItems
		 */
		$paymentBasketItems = $payment->getPayableItemCollection()->getBasketItems();
		$unReservedBasketItems = [];
		$unReservedProductsIds = [];
		foreach ($paymentBasketItems as $payableItem)
		{
			$basketId = (int)$payableItem->getField('ENTITY_ID');
			$unReservedBasketItems[$basketId] = (float)$payableItem->getField('QUANTITY');

			/**
			 * @var BasketItem $entityObject
			 */
			$entityObject = $payableItem->getEntityObject();
			$unReservedProductsIds[] = $entityObject->getProductId();
		}

		if (empty($unReservedBasketItems))
		{
			return $result;
		}

		$productRowToBasket = BasketService::getInstance()->getRowIdsToBasketIdsByEntity($ownerTypeId, $ownerId);
		$existReserves = ProductRowReservationTable::getList([
			'select' => [
				'ID',
				'ROW_ID',
				'RESERVE_QUANTITY',
				'DATE_RESERVE_END',
				'STORE_ID',
			],
			'filter' => [
				'=ROW_ID' => array_keys($productRowToBasket),
			],
		]);
		foreach ($existReserves as $reserve)
		{
			$rowId = (int)$reserve['ROW_ID'];
			$basketId = $productRowToBasket[$rowId] ?? null;
			if (!$basketId)
			{
				continue;
			}

			$unReservedQuantity = (float)($unReservedBasketItems[$basketId] ?? 0.0);
			if (empty($unReservedQuantity))
			{
				continue;
			}

			$reserveQuantity = (float)$reserve['RESERVE_QUANTITY'];
			$newReserveQuantity = max(0, $reserveQuantity - $unReservedQuantity);

			$saveResult = $this->saveCrmReserve([
				'ID' => $reserve['ID'],
				'RESERVE_QUANTITY' => $newReserveQuantity,
			]);

			$reserveInfo = $result->addReserveInfo(
				$rowId,
				$newReserveQuantity,
				$newReserveQuantity - $reserveQuantity
			);
			$reserveInfo->setStoreId((int)$reserve['STORE_ID']);
			$reserveInfo->setDateReserveEnd((string)$reserve['DATE_RESERVE_END']);

			$result->addErrors($saveResult->getErrors());
		}

		return $result;
	}

	/**
	 * Info of reserves product rows by ids.
	 *
	 * @param array $rowsIds
	 *
	 * @return array
	 */
	protected function getReservesByRowsIds(array $rowsIds): array
	{
		if (empty($rowsIds))
		{
			return [];
		}

		$result = [];

		$rows = ProductRowReservationTable::getList([
			'select' => [
				'ID',
				'ROW_ID',
				'STORE_ID',
				'DATE_RESERVE_END',
				'RESERVE_QUANTITY',
			],
			'filter' => [
				'=ROW_ID' => $rowsIds,
			],
		]);
		foreach ($rows as $row)
		{
			$rowId = (int)$row['ROW_ID'];
			$result[$rowId] = $row;
		}

		return $result;
	}

	/**
	 * Paid products rows.
	 * Payment information is obtained from the order binded to the entity.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 *
	 * @return array map in format `['rowId' => 'paidQuantity']`
	 */
	protected function getPaidProductRows(int $ownerTypeId, int $ownerId): array
	{
		$rows = EntityBinding::getList([
			'select' => [
				'ORDER_ID',
			],
			'filter' => [
				'=OWNER_TYPE_ID' => $ownerTypeId,
				'=OWNER_ID' => $ownerId,
			],
		]);

		$entityOrderIds = array_column($rows->fetchAll(), 'ORDER_ID');
		if (!$entityOrderIds)
		{
			return [];
		}

		// TODO: after realization order synchronizer refactor with link `b_crm_product_row.XML_ID` = `b_sale_basket.ID`
		$paidProducts = [];
		$rows = PayableItemCollection::getList([
			'select' => [
				'PRODUCT_ID' => 'BASKET.PRODUCT_ID',
				'QUANTITY',
			],
			'filter' => [
				'=ENTITY_TYPE' => Registry::ENTITY_BASKET_ITEM,
				'=PAYMENT.ORDER_ID' => $entityOrderIds,
				'=PAYMENT.PAID' => 'Y',
				'!BASKET.PRODUCT_ID' => null,
			],
		]);
		foreach ($rows as $row)
		{
			$productId = (int)$row['PRODUCT_ID'];
			$paidProducts[$productId] ??= 0.0;
			$paidProducts[$productId] += (float)$row['QUANTITY'];
		}

		if (empty($paidProducts))
		{
			return [];
		}

		$paidProductRows = [];
		$rows = ProductRowTable::getList([
			'select' => [
				'ID',
				'PRODUCT_ID',
			],
			'filter' => [
				'=OWNER_TYPE' => CCrmOwnerTypeAbbr::ResolveByTypeID($ownerTypeId),
				'=OWNER_ID' => $ownerId,
				'=PRODUCT_ID' => array_keys($paidProducts),
				'!@TYPE' => ReservationService::getInstance()->getRestrictedProductTypes(),
			],
		]);
		foreach ($rows as $row)
		{
			$productId = (int)$row['PRODUCT_ID'];
			$paidQuantity = $paidProducts[$productId] ?? null;

			if ($paidQuantity)
			{
				$rowId = (int)$row['ID'];
				$paidProductRows[$rowId] = $paidQuantity;
			}
		}

		return $paidProductRows;
	}

	/**
	 * Save CRM reserve.
	 *
	 * @param array $fields
	 *
	 * @return UpdateResult|AddResult
	 */
	protected function saveCrmReserve(array $fields)
	{
		$id = $fields['ID'] ?? null;
		if (isset($id))
		{
			unset($fields['ID']);

			return ProductRowReservationTable::update($id, $fields);
		}

		return ProductRowReservationTable::add($fields);
	}

	/**
	 * The quantity of product rows deducted.
	 *
	 * @param array $rowsIds
	 *
	 * @return array in format `[rowId => deductedQuantity]`
	 */
	public function getDeductedProductRows(array $rowsIds): array
	{
		return ShipmentService::getInstance()->getDeductedProductRowsQuantity($rowsIds);
	}
}
