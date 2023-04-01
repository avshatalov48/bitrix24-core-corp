<?php

namespace Bitrix\Crm\Reservation\Agent;

use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\Order\OrderDealSynchronizer;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\ProductRowReservation;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
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

		$dealReserves = [];
		$rows = ProductRowReservationTable::getList([
			'select' => [
				'ID',
				'RESERVE_QUANTITY',
				'PRODUCT_ROW_ID' => 'PRODUCT_ROW.ID',
				'PRODUCT_ROW_OWNER_ID' => 'PRODUCT_ROW.OWNER_ID',
			],
			'filter' => [
				'>RESERVE_QUANTITY' => 0,
				'<=DATE_RESERVE_END' => new DateTime(),
				'!=PRODUCT_ROW.DEAL_OWNER.CLOSED' => 'Y',
			],
			'limit' => self::RECORD_LIMIT,
		]);
		foreach ($rows as $row)
		{
			$rowId = (int)$row['PRODUCT_ROW_ID'];
			$ownerId = (int)$row['PRODUCT_ROW_OWNER_ID'];

			$validRecord = $rowId > 0 && $ownerId > 0;
			if (!$validRecord)
			{
				continue;
			}

			$dealReserves[$ownerId] ??= [];
			$dealReserves[$ownerId][$rowId] = [
				'ID' => $row['ID'],
				'RESERVE_QUANTITY' => $row['RESERVE_QUANTITY'],
			];

			$processedRecords++;
		}

		if (empty($dealReserves))
		{
			return 0;
		}

		/**
		 * @var Deal[] $deals
		 */
		$deals = $factory->getItems([
			'select' => [
				'*',
				Deal::FIELD_NAME_PRODUCTS,
			],
			'filter' => [
				'=ID' => array_keys($dealReserves),
			],
		]);
		foreach ($deals as $deal)
		{
			/**
			 * @var ProductRow[] $productRows
			 */
			$productRows = $deal->getProductRows();
			if (empty($productRows))
			{
				continue;
			}

			$dealId = $deal->getId();
			foreach ($productRows as $productRow)
			{
				$rowId = $productRow->getId();
				if (!isset($dealReserves[$dealId][$rowId]))
				{
					continue;
				}

				/**
				 * @var ProductRowReservation $reservation
				 */
				$reservation = $productRow->getProductRowReservation();
				if ($reservation)
				{
					$reservation->setReserveQuantity(0);
					$reservation->setDateReserveEnd(null);
				}
			}

			$operation = $factory->getUpdateOperation($deal);
			$result = $operation->launch();
			$this->logResult("update deal '{$dealId}'", $result);

			if ($result->isSuccess())
			{
				unset($dealReserves[$dealId]);
			}
		}

		$this->forceDeleteReserves($dealReserves);

		return $processedRecords;
	}

	/**
	 * Delete reserves without deal saving.
	 *
	 * @param array $dealReserves
	 *
	 * @return void
	 */
	private function forceDeleteReserves(array $dealReserves): void
	{
		$db = Application::getConnection();
		$syncronizer = new OrderDealSynchronizer();

		foreach ($dealReserves as $dealId => $rows)
		{
			if (empty($rows))
			{
				continue;
			}

			$reservationResult = new ReservationResult();
			foreach ($rows as $rowId => $reserve)
			{
				$reserveQuantity = (float)$reserve['RESERVE_QUANTITY'];
				$reservationResult->addReserveInfo(
					$rowId,
					0,
					-$reserveQuantity
				);
			}

			try
			{
				$db->startTransaction();

				ProductRowReservationTable::deleteByFilter([
					'=ROW_ID' => array_keys($rows),
				]);

				$syncronizer->syncOrderReservesFromDeal($dealId, $reservationResult);

				$db->commitTransaction();
			}
			catch (Throwable $e)
			{
				$db->rollbackTransaction();

				throw $e;
			}

			if (isset($this->logger))
			{
				$this->logger->info('Force delete reserves for deal: ' . $dealId);
			}
		}
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
