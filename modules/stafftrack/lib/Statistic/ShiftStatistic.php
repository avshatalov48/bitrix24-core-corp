<?php

namespace Bitrix\StaffTrack\Statistic;

use Bitrix\Main\Type\Contract\Arrayable;

class ShiftStatistic implements Arrayable
{
	/** @var int $userId */
	private int $userId;
	/** @var int $shiftCounter */
	private int $shiftCounter = 0;
	/** @var int[] $locationCounter */

	/**
	 * @param int $userId
	 */
	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return void
	 */
	public function increaseShiftCounter(): void
	{
		$this->shiftCounter++;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'userId' => $this->userId,
			'shiftCounter' => $this->shiftCounter,
		];
	}
}