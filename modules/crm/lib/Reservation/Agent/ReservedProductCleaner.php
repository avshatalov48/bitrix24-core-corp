<?php

namespace Bitrix\Crm\Reservation\Agent;

use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\ProductRowReservation;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;
use CCrmOwnerType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

/**
 * Cleaner crm product's reserves.
 *
 * @see \Bitrix\Sale\Helpers\ReservedProductCleaner reference from `sale` module
 */
class ReservedProductCleaner extends Stepper implements LoggerAwareInterface
{
	use LoggerAwareTrait;

	private const RECORD_LIMIT = 100;

	protected static $moduleId = 'crm';

	public function __construct()
	{
		$logger = Logger::create('crm.reservation.reservedProductCleaner');
		if ($logger)
		{
			$this->setLogger($logger);
		}
	}

	/**
	 * Run stepper as agent, for periodic runs.
	 *
	 * @return string
	 */
	public static function runAgent(): string
	{
		self::bind(0);

		return __METHOD__ . '();';
	}

	/**
	 * @inheritDoc
	 */
	public function execute(array &$result)
	{
		$processedRecords = $this->releaseDealReserves();
		if ($processedRecords < self::RECORD_LIMIT)
		{
			return self::FINISH_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}

	/**
	 * Releases deal reserves.
	 *
	 * Releases only reserves of NOT closed deals!
	 *
	 * @return int
	 */
	private function releaseDealReserves(): int
	{
		$processedRecords = 0;

		/**
		 * @var \Bitrix\Crm\Service\Factory\Deal $factory
		 */
		$factory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);
		if (!$factory)
		{
			return $processedRecords;
		}

		$dealReserves = $this->getDealReserves($processedRecords);

		if (empty($dealReserves))
		{
			return 0;
		}

		$dealIds = array_keys($dealReserves);
		$deals = $this->getDeals($factory, $dealIds);
		foreach ($deals as $deal)
		{
			$productRowIds = array_keys($dealReserves[$deal->getId()]);
			if ($this->releaseProductRowReserves($deal, $productRowIds))
			{
				$this->updateDeal($factory, $deal);
			}
		}

		return $processedRecords;
	}

	/**
	 * @param int $processedRecords
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getDealReserves(int &$processedRecords): array
	{
		$productReservationRows = ProductRowReservationTable::getList(
			[
				'select' => [
					'ID',
					'RESERVE_QUANTITY',
					'PRODUCT_ROW_ID' => 'PRODUCT_ROW.ID',
					'PRODUCT_ROW_OWNER_ID' => 'PRODUCT_ROW.OWNER_ID',
				],
				'filter' => [
					'=PRODUCT_ROW.OWNER_TYPE' => CCrmOwnerType::Deal,
					'>RESERVE_QUANTITY' => 0,
					'<=DATE_RESERVE_END' => new DateTime(),
					'!=PRODUCT_ROW.DEAL_OWNER.CLOSED' => 'Y',
				],
				'limit' => self::RECORD_LIMIT,
			]
		);

		$dealReserves = [];
		foreach ($productReservationRows as $productReservation) {
			$productRowId = (int)$productReservation['PRODUCT_ROW_ID'];
			$dealId = (int)$productReservation['PRODUCT_ROW_OWNER_ID'];

			$validRecord = $productRowId > 0 && $dealId > 0;
			if (!$validRecord) {
				continue;
			}

			$dealReserves[$dealId] ??= [];
			$dealReserves[$dealId][$productRowId] = [
				'ID' => $productReservation['ID'],
				'RESERVE_QUANTITY' => $productReservation['RESERVE_QUANTITY'],
			];

			$processedRecords++;
		}

		return $dealReserves;
	}

	/**
	 * @param \Bitrix\Crm\Service\Factory\Deal $factory
	 * @param array $dealIds
	 * @return Deal[]
	 */
	private function getDeals(\Bitrix\Crm\Service\Factory\Deal $factory, array $dealIds): array
	{
		/**
		 * @var Deal[] $deals
		 */
		$deals = $factory->getItems(
			[
				'select' => [
					'*',
					Deal::FIELD_NAME_PRODUCTS,
				],
				'filter' => [
					'=ID' => $dealIds,
				],
			]
		);

		return $deals;
	}

	/**
	 * @param Deal $deal
	 * @param array $productRowIds
	 * @return bool isReleased
	 */
	private function releaseProductRowReserves(Deal $deal, array $productRowIds): bool
	{
		$isReleased = false;

		/**
		 * @var ProductRowCollection $productRows
		 */
		$productRows = $deal->getProductRows();
		if (empty($productRows))
		{
			return false;
		}

		foreach ($productRowIds as $productRowId)
		{
			$productRow = $productRows->getByPrimary($productRowId);
			$reservation = $productRow->getProductRowReservation();

			if ($reservation)
			{
				$reservation->setReserveQuantity(0);
				$isReleased = true;
			}
		}

		return $isReleased;
	}

	/**
	 * @param \Bitrix\Crm\Service\Factory\Deal $factory
	 * @param Deal $deal
	 * @return void
	 */
	private function updateDeal(\Bitrix\Crm\Service\Factory\Deal $factory, Deal $deal): void
	{
		$operation = $factory->getUpdateOperation($deal);
		$operation->disableAllChecks();
		$result = $operation->launch();
		$this->logResult("update deal '{$deal->getId()}'", $result);
	}

	/**
	 * Log result info.
	 *
	 * @param string $message
	 * @param Result $result
	 *
	 * @return void
	 */
	private function logResult(string $message, Result $result): void
	{
		if (!$this->logger)
		{
			return;
		}

		if ($result->isSuccess())
		{
			$this->logger->info($message . ": success\n");
		}
		else
		{
			$errors = join(', ', $result->getErrorMessages());
			$this->logger->error($message . ": {$errors}\n");
		}
	}
}
