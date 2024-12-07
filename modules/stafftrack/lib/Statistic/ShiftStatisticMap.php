<?php

namespace Bitrix\StaffTrack\Statistic;

use Bitrix\Main\Type\Contract\Arrayable;

class ShiftStatisticMap implements Arrayable
{
	/** @var ShiftStatistic[]  */
	private array $items = [];

	/**
	 * @param int $key
	 * @return ShiftStatistic|null
	 */
	public function get(int $key): ?ShiftStatistic
	{
		return $this->items[$key] ?? null;
	}

	/**
	 * @param int $key
	 * @param ShiftStatistic $statistic
	 * @return void
	 */
	public function set(int $key, ShiftStatistic $statistic): void
	{
		$this->items[$key] = $statistic;
	}

	/**
	 * @param array $userIdList
	 * @return void
	 */
	public function fill(array $userIdList): void
	{
		foreach ($userIdList as $userId)
		{
			$this->items[$userId] = new ShiftStatistic($userId);
		}
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$result = [];

		foreach ($this->items as $key => $item)
		{
			$result[$key] = $item->toArray();
		}

		return $result;
	}
}