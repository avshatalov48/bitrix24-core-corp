<?php

namespace Bitrix\Crm\Service\Sale\Reservation;

use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Deal;
use Bitrix\Crm\Integration\Sale\Reservation\Config\EntityFactory;
use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\ProductRowTable;
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
use Bitrix\Sale\Configuration;
use Bitrix\Sale\Order;
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
	 * @return Date|null
	 */
	public function getDefaultDateReserveEnd(): ?Date
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
	 * Reservation all products of deal.
	 *
	 * @param int $dealId
	 *
	 * @return Result
	 */
	public function reservationProductsByDeal(int $dealId): Result
	{
		return $this->reservationProducts(CCrmOwnerType::Deal, $dealId);
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
		$strategy = $this->getStrategy();
		if (!$strategy)
		{
			return new Result();
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
			[$ownerTypeId, $ownerId] = $this->getOwnerForRow($productRowId);

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
			 * @var BasketItem $basketItem
			 */
			$basketItem = $order->getBasket()->getItemById($basketId);
			if ($basketItem)
			{
				$basketReserve = $basketItem->getReserveQuantityCollection()->current();
				if ($basketReserve)
				{
					$this->setReserveMap($rowId, $basketReserve->getId());
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
	 * @param int $productRowId
	 *
	 * @return array in format [ownerTypeId, ownerId]
	 */
	private function getOwnerForRow(int $productRowId): array
	{
		$row = ProductRowTable::getRow([
			'select' => [
				'OWNER_ID',
				'OWNER_TYPE',
			],
			'filter' => [
				'=ID' => $productRowId,
			],
		]);
		if ($row)
		{
			return [
				(int)CCrmOwnerTypeAbbr::ResolveTypeID($row['OWNER_TYPE']),
				(int)$row['OWNER_ID'],
			];
		}

		return [null, null];
	}
}
