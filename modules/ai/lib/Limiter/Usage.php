<?php

namespace Bitrix\AI\Limiter;

use Bitrix\AI\Config;
use Bitrix\AI\Context;
use Bitrix\AI\Model\UsageTable;
use Bitrix\Main\Type\DateTime;

class Usage
{
	protected const DEFAULT_COST = 1;

	private const PERIODS = [
		Period\Daily::class,
		Period\Monthly::class,
	];

	public function __construct(
		private Context $context,
	) {}

	/**
	 * Increments usage count for specific date and time.
	 *
	 * @param int $value Changed count.
	 * @param DateTime|null $time Datetime for period.
	 * @return void
	 */
	public function increment(int $value, ?DateTime $time = null): void
	{
		$this->updateCount(+1 * abs($value), $time);
	}

	/**
	 * Decrements usage count for specific date and time.
	 *
	 * @param int $value Changed count.
	 * @param DateTime|null $time Datetime for period.
	 * @return void
	 */
	public function decrement(int $value, ?DateTime $time = null): void
	{
		$this->updateCount(-1 * abs($value), $time);
	}

	/**
	 * Checks that current request is in limits.
	 *
	 * @param string|null $limitCode Will be returned code of limit.
	 * @param int $cost
	 * @return bool
	 */
	public function isInLimit(?string &$limitCode = null, int $cost = self::DEFAULT_COST): bool
	{
		if (Config::getValue('check_limits') !== 'Y')
		{
			return true;
		}

		foreach ($this->getAvailablePeriods() as $periodInstance)
		{
			$periodInstance = new $periodInstance($this->context);
			if (($periodInstance->getCurrentUsage() + $cost) > $periodInstance->getMaximumUsage())
			{
				$limitCode = (new \ReflectionClass($periodInstance))->getShortName();
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates usage count for specific date and time.
	 *
	 * @param int $value Changed count.
	 * @param DateTime|null $time Datetime for period.
	 * @return void
	 */
	private function updateCount(int $value, ?DateTime $time = null): void
	{
		if (!$value)
		{
			return;
		}

		$periods = $this->getPeriods();

		// sets usage counts for periods
		foreach ($this->getAvailablePeriods() as $periodInstance)
		{
			$period = (new $periodInstance($this->context))->getCode();

			if (empty($periods[$period]))
			{
				UsageTable::add([
					'USAGE_PERIOD' => $period,
					'USAGE_COUNT' => $value,
					'USER_ID' => $this->context->getUserId(),
				])->isSuccess();
			}
			else
			{
				UsageTable::update($periods[$period]['ID'], [
					'USAGE_COUNT' => max($periods[$period]['USAGE_COUNT'] + $value, 0),
					'DATE_MODIFY' => $time ?: new DateTime,
				])->isSuccess();
				unset($periods[$period]);
			}
		}

		// remove unused old periods
		foreach ($periods as $period)
		{
			UsageTable::delete($period['ID'])->isSuccess();
		}
	}

	/**
	 * Returns available periods.
	 *
	 * @return Period\IPeriod[]
	 */
	private function getAvailablePeriods(): array
	{
		return self::PERIODS;
	}

	/**
	 * Returns all periods for current Context.
	 *
	 * @return array
	 */
	private function getPeriods(): array
	{
		$periods = [];

		$rows = UsageTable::query()
			->setSelect(['ID', 'USAGE_PERIOD', 'USAGE_COUNT'])
			->where('USER_ID', $this->context->getUserId())
			->fetchAll()
		;
		foreach ($rows as $row)
		{
			$periods[$row['USAGE_PERIOD']] = $row;
		}

		return $periods;
	}

	/**
	 * Finally removes all records for the user.
	 *
	 * @param int $userId User id.
	 * @return void
	 */
	public static function deleteForUser(int $userId): void
	{
		$rows = UsageTable::query()
			->setSelect(['ID'])
			->where('USER_ID', $userId)
			->fetchAll()
		;
		foreach ($rows as $row)
		{
			UsageTable::delete($row['ID'])->isSuccess();
		}
	}
}
