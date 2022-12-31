<?php

namespace Bitrix\Crm\Reservation\Agent;

use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\ProductRowReservation;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;
use CCrmOwnerType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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

			$dealReserves[$ownerId] ??= [];
			$dealReserves[$ownerId][$rowId] = true;

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

			foreach ($productRows as $productRow)
			{
				$rowId = $productRow->getId();
				if (!isset($dealReserves[$deal->getId()][$rowId]))
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
				}
			}

			$operation = $factory->getUpdateOperation($deal);
			$result = $operation->launch();
			$this->logResult("update deal '{$deal->getId()}'", $result);
		}

		return $processedRecords;
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
