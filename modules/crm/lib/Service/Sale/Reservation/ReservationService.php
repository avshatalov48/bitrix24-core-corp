<?php

namespace Bitrix\Crm\Service\Sale\Reservation;

use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Deal;
use Bitrix\Crm\Integration\Sale\Reservation\Config\EntityFactory;
use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\ProductType;
use Bitrix\Crm\Reservation\BasketReservation;
use Bitrix\Crm\Reservation\Internals\ProductReservationMapTable;
use Bitrix\Crm\Reservation\Strategy\Factory\OptionStrategyFactory;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Reservation\Strategy\ReservePaidProductsStrategy;
use Bitrix\Crm\Reservation\Strategy\ReserveQuantityEqualProductQuantityStrategy;
use Bitrix\Crm\Reservation\Strategy\Strategy;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Configuration;
use Bitrix\Sale\Order;
use Bitrix\Sale\Reservation\BasketReservationService;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;

/**
 * Service for work with reservation of product rows.
 */
class ReservationService
{
	public function __construct()
	{
		Loader::requireModule('sale');
		Loc::loadMessages(__FILE__);
	}

	/**
	 * Service instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		return ServiceLocator::getInstance()->get('crm.reservation');
	}

	/**
	 * Default store id, if it is not filled.
	 *
	 * @return int
	 */
	private function getDefaultStoreId(): int
	{
		return Configuration::getDefaultStoreId();
	}

	/**
	 * Default date reserve end, if it is not filled.
	 *
	 * @return Date
	 */
	public function getDefaultDateReserveEnd(): Date
	{
		$config = EntityFactory::make(Deal::CODE);
		$days = (int)$config->getReserveWithdrawalPeriod();

		return (new Date())->add($days . 'D');
	}

	/**
	 * Get reservation strategy.
	 *
	 * @return Strategy|null
	 */
	private function getStrategy(): ?Strategy
	{
		$strategy = (new OptionStrategyFactory)->create();
		if (!$strategy)
		{
			return null;
		}

		if (
			$strategy instanceof ReserveQuantityEqualProductQuantityStrategy
			|| $strategy instanceof ReservePaidProductsStrategy
		)
		{
			$strategy->defaultStoreId = $this->getDefaultStoreId();
			$strategy->defaultDateReserveEnd = $this->getDefaultDateReserveEnd();
		}

		return $strategy;
	}

	/**
	 * Check as reserve strategy is `ReserveQuantityEqualProductQuantityStrategy`.
	 *
	 * @return bool
	 */
	public function isReserveEqualProductQuantity(): bool
	{
		return $this->getStrategy() instanceof ReserveQuantityEqualProductQuantityStrategy;
	}

	/**
	 * Reservation products by payment.
	 *
	 * @param Payment $payment
	 *
	 * @return Result
	 */
	public function reservationProductsByPayment(Payment $payment): Result
	{
		$result = new Result();

		$binding = $payment->getOrder()->getEntityBinding();
		if (!$binding)
		{
			$result->addError(
				new Error('Order not binded')
			);
			return $result;
		}

		$ownerTypeId = $binding->getOwnerTypeId();
		$ownerId = $binding->getOwnerId();

		return $this->reservationProducts($ownerTypeId, $ownerId);
	}

	/**
	 * Remove payment reservations.
	 *
	 * Called when payment canceled.
	 * Working ONLY if current reservation strategy is 'ReservePaidProductsStrategy'.
	 *
	 * @param Payment $payment
	 *
	 * @return Result
	 */
	public function removeReservesProductsByPayment(Payment $payment): Result
	{
		$result = new Result();

		$strategy = $this->getStrategy();
		if ($strategy instanceof ReservePaidProductsStrategy)
		{
			$binding = $payment->getOrder()->getEntityBinding();
			if (!$binding)
			{
				$result->addError(
					new Error('Order not binded')
				);
				return $result;
			}

			$ownerTypeId = $binding->getOwnerTypeId();
			$ownerId = $binding->getOwnerId();

			$result = $strategy->removeReservesPaymentProducts($ownerTypeId, $ownerId, $payment);
			if ($result->isSuccess() && $ownerTypeId === CCrmOwnerType::Deal)
			{
				$this->synchronizeOrderReservesForDealByReservationResult($ownerId, $result);
			}
		}

		return $result;
	}

	/**
	 * Reservation products of deal.
	 *
	 * @param int $dealId
	 * @param array $productRows
	 *
	 * @return Result
	 */
	public function reservationProductsByDealProductRows(int $dealId, array $productRows): Result
	{
		$result = new Result();

		$isHasManualReserves = false;
		foreach ($productRows as $row)
		{
			if (isset($row['TYPE']) && $this->isRestrictedType((int)$row['TYPE']))
			{
				continue;
			}

			$rowId = (int)($row['ID'] ?? 0);
			if (!$rowId)
			{
				continue;
			}

			$storeId = $row['STORE_ID'] ?? null;
			$dateReserveEnd = $row['DATE_RESERVE_END'] ?? null;
			$reserveQuantity = $row['INPUT_RESERVE_QUANTITY'] ?? $row['RESERVE_QUANTITY'] ?? null;

			if (empty($storeId) && empty($dateReserveEnd) && empty($reserveQuantity))
			{
				continue;
			}

			$reserveQuantity = (float)$reserveQuantity;
			$storeId = $storeId ? (int)$storeId : null;
			$dateReserveEnd = $dateReserveEnd ? Date::createFromText($dateReserveEnd) : null;

			$reserveResult = $this->reservationProductRow($rowId, $reserveQuantity, $storeId, $dateReserveEnd);
			$result->addErrors($reserveResult->getErrors());

			$isHasManualReserves = true;
		}

		if (!$isHasManualReserves)
		{
			return $this->reservationProducts(CCrmOwnerType::Deal, $dealId);
		}

		return $result;
	}

	/**
	 * Reservation all products of entity.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 *
	 * @return Result
	 */
	public function reservationProducts(int $ownerTypeId, int $ownerId): Result
	{
		$strategy = $this->getStrategy();
		if (!$strategy)
		{
			return new Result();
		}

		$result = $strategy->reservation($ownerTypeId, $ownerId);
		if ($result->isSuccess() && $ownerTypeId === CCrmOwnerType::Deal)
		{
			$this->synchronizeOrderReservesForDealByReservationResult($ownerId, $result);
		}

		return $result;
	}

	/**
	 * Reservation one concrete product row.
	 *
	 * @param int $productRowId
	 * @param float $quantity
	 * @param int|null $storeId
	 * @param Date|null $dateReserveEnd
	 *
	 * @return Result
	 */
	public function reservationProductRow(int $productRowId, float $quantity, ?int $storeId = null, ?Date $dateReserveEnd = null): Result
	{
		$result = new Result();

		$strategy = $this->getStrategy();
		if (!$strategy)
		{
			return $result;
		}

		$productRow = $this->getProductRow($productRowId);
		if (empty($productRow))
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_SERVICE_PRODUCT_NOT_FOUND'))
			);
			return $result;
		}

		if ($this->isRestrictedType((int)$productRow['TYPE']))
		{
			$result->addError(new Error(
				Loc::getMessage('CRM_RESERVATION_SERVICE_PRODUCT_NOT_SUPPORT_RESERVATION'))
			);
			return $result;
		}

		if (!isset($storeId))
		{
			$storeId = $this->getDefaultStoreId();
		}
		if (!isset($dateReserveEnd))
		{
			$dateReserveEnd = $this->getDefaultDateReserveEnd();
		}

		$result = $strategy->reservationProductRow($productRowId, $quantity, $storeId, $dateReserveEnd);
		if ($result->isSuccess())
		{
			[$ownerTypeId, $ownerId] = $this->getOwnerForRow($productRow);

			if ($ownerTypeId === CCrmOwnerType::Deal)
			{
				$this->synchronizeOrderReservesForDealByReservationResult($ownerId, $result);
			}
		}

		return $result;
	}

	/**
	 * Filling basket item fields `RESERVE_QUANTITY` and `RESERVE_ID` for product rows.
	 *
	 * @param array $productRows
	 * @param bool $fillOnlyReserveId
	 *
	 * @return array
	 */
	public function fillBasketReserves(array $productRows, bool $fillOnlyReserveId = false): array
	{
		if (empty($productRows))
		{
			return [];
		}

		$basketReservation = new BasketReservation();
		$basketReservation->addProducts($productRows);

		$reservedProducts = $basketReservation->getReservedProducts();
		foreach ($productRows as &$row)
		{
			$reservedProductData = $reservedProducts[$row['ID']] ?? null;
			$row['RESERVE_ID'] = $reservedProductData['RESERVE_ID'] ?? null;

			if (!$fillOnlyReserveId)
			{
				$row['RESERVE_QUANTITY'] = $reservedProductData['RESERVE_QUANTITY'] ?? 0.0;
			}
		}
		unset($row);

		return $productRows;
	}

	/**
	 * Synchronize deal product rows reserves with order basket items by result of reservation.
	 *
	 * @param int $dealId
	 * @param ReservationResult $result
	 *
	 * @return void
	 */
	private function synchronizeOrderReservesForDealByReservationResult(int $dealId, ReservationResult $result): void
	{
		$syncronizer = new OrderDealSynchronizer();
		$syncronizer->syncOrderReservesFromDeal($dealId, $result);
	}

	/**
	 * Synchronize deal product rows reserves with order basket items by result of reservation.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @param Order $order
	 *
	 * @return void
	 */
	public function mappingReservations(int $ownerTypeId, int $ownerId, Order $order): Result
	{
		$result = new Result();

		$productRow2basket = BasketService::getInstance()->getRowIdsToBasketIdsByEntity(
			$ownerTypeId,
			$ownerId
		);
		if (empty($productRow2basket))
		{
			return $result;
		}

		foreach ($productRow2basket as $rowId => $basketId)
		{
			/**
			 * @var \Bitrix\Sale\BasketItem $basketItem
			 */
			$basketItem = $order->getBasket()->getItemById($basketId);
			if ($basketItem)
			{
				/** @var \Bitrix\Sale\ReserveQuantityCollection $reserveCollection */
				$reserveCollection = $basketItem->getReserveQuantityCollection();
				if ($reserveCollection)
				{
					$basketReserve = $reserveCollection->current();
					if ($basketReserve)
					{
						$this->setReserveMap($rowId, $basketReserve->getId());
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Set link between product row and basket reservation.
	 *
	 * @param int $rowId
	 * @param int $basketReservationId
	 *
	 * @return Result
	 */
	private function setReserveMap(int $rowId, int $basketReservationId): Result
	{
		$exist = ProductReservationMapTable::getRow([
			'filter' => [
				'=PRODUCT_ROW_ID' => $rowId,
			],
		]);
		if ($exist)
		{
			return ProductReservationMapTable::update($exist['ID'], [
				'BASKET_RESERVATION_ID' => $basketReservationId,
			]);
		}

		return ProductReservationMapTable::add([
			'PRODUCT_ROW_ID' => $rowId,
			'BASKET_RESERVATION_ID' => $basketReservationId,
		]);
	}

	/**
	 * Owner info of row.
	 *
	 * @param array $productRow
	 * @return array in format [ownerTypeId, ownerId]
	 */
	private function getOwnerForRow(array $productRow): array
	{
		return [
			(int)CCrmOwnerTypeAbbr::ResolveTypeID($productRow['OWNER_TYPE']),
			(int)$productRow['OWNER_ID'],
		];
	}

	/**
	 * Get product row.
	 *
	 * @param int $rowId
	 *
	 * @return array|null
	 */
	private function getProductRow(int $rowId): ?array
	{
		return ProductRowTable::getRow([
			'select' => [
				'ID',
				'QUANTITY',
				'TYPE',
				'OWNER_ID',
				'OWNER_TYPE',
			],
			'filter' => [
				'=ID' => $rowId,
			],
		]);
	}

	public function isRestrictedType(int $type): bool
	{
		return in_array($type, $this->getRestrictedProductTypes(), true);
	}

	public function getRestrictedProductTypes(): array
	{
		return [
			ProductType::TYPE_SET,
			ProductType::TYPE_SERVICE,
		];
	}
}
