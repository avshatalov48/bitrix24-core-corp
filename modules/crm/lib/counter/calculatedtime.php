<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;

class CalculatedTime
{
	private int $userId;
	private string $counterCode;
	private ?int $lastCalculatedTimestamp = null;

	private const MAX_CALCULATION_TIME = 600;

	public function __construct(int $userId, string $counterCode)
	{
		$this->userId = $userId;
		$this->counterCode = $counterCode;
	}

	public function wasCalculatedToday(): bool
	{
		return $this->getLastCalculatedTimestamp() >= $this->getTodayTimestamp();
	}

	public function tryStartCalculation(): bool
	{
		$calculatedTime = CounterCalculatedTimeTable::query()
			->where('USER_ID', $this->userId)
			->where('CODE', $this->counterCode)
			->setSelect(['IS_CALCULATING', 'CALCULATION_STARTED_AT'])
			->setLimit(1)
			->fetch();
		if (
			!$calculatedTime
			|| $calculatedTime['IS_CALCULATING'] !== 'Y'
			|| (
				$calculatedTime['CALCULATION_STARTED_AT']
				&& ($calculatedTime['CALCULATION_STARTED_AT']->getTimestamp() + self::MAX_CALCULATION_TIME < time())
			)
		)
		{
			$today = DateTime::createFromTimestamp($this->getTodayTimestamp());

			if (!$calculatedTime)
			{
				try
				{
					CounterCalculatedTimeTable::add([
						'USER_ID' => $this->userId,
						'CODE' => $this->counterCode,
						'IS_CALCULATING' => true,
						'CALCULATION_STARTED_AT' => new DateTime(),
						'CALCULATED_AT' => $today,
					]);
				}
				catch (SqlQueryException $e)
				{
					if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
					{
						return false;
					}
				}
			}
			else
			{
				CounterCalculatedTimeTable::update([
					'USER_ID' => $this->userId,
					'CODE' => $this->counterCode,
				], [
					'IS_CALCULATING' => true,
					'CALCULATION_STARTED_AT' => new DateTime(),
					'CALCULATED_AT' => $today,
				]);
			}

			return true;
		}

		return false;
	}

	public function finishCalculation(): void
	{
		CounterCalculatedTimeTable::update([
			'USER_ID' => $this->userId,
			'CODE' => $this->counterCode,
		], [
			'IS_CALCULATING' => false,
			'CALCULATION_STARTED_AT' => null,
			'CALCULATED_AT' => DateTime::createFromTimestamp($this->getTodayTimestamp()),
		]);
	}


	private function getLastCalculatedTimestamp(): int
	{
		if (is_null($this->lastCalculatedTimestamp))
		{
			$this->lastCalculatedTimestamp = CounterCalculatedTimeTable::getCalculatedAt(
				$this->userId,
				$this->counterCode,
			);
		}

		return $this->lastCalculatedTimestamp;
	}

	private function getTodayTimestamp(): int
	{
		return mktime(0, 0, 0, date('n'), date('j'), date('Y'));
	}
}
