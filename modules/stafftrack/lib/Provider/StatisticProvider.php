<?php

namespace Bitrix\StaffTrack\Provider;

use Bitrix\Main\Type\DateTime;
use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Model\ShiftCollection;
use Bitrix\StaffTrack\Statistic\ShiftStatisticMap;

class StatisticProvider
{
	/** @var int $userId */
	private int $userId;

	/**
	 * @param int $userId
	 */
	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param int $nodeId
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @return ShiftStatisticMap
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByNode(
		int $nodeId,
		DateTime $dateFrom,
		DateTime $dateTo
	): ShiftStatisticMap
	{
		// TODO: get user list with info from HR module
		$userIdList = [1, 9, 10, 11];

		return $this->getByUserIdList($userIdList, $dateFrom, $dateTo);
	}

	/**
	 * @param array $userIdList
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @return ShiftStatisticMap
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByUserIdList(
		array $userIdList,
		DateTime $dateFrom,
		DateTime $dateTo
	): ShiftStatisticMap
	{
		$shiftCollection = ShiftProvider::getInstance($this->userId)->list(
			filter: [
				'DATE_FROM' => $dateFrom,
				'DATE_TO' => $dateTo,
				'USER_ID' => $userIdList,
				'STATUS' => Status::WORKING->value,
			],
			select: [
				'ID',
				'SHIFT_DATE',
				'USER_ID',
				'STATUS',
			],
			order: [
				'USER_ID' => 'ASC',
			],
		);

		return $this->generateShiftStatisticMap($shiftCollection, $userIdList);
	}

	/**
	 * @param ShiftCollection $shiftCollection
	 * @param array $userIdList
	 * @return ShiftStatisticMap
	 */
	private function generateShiftStatisticMap(ShiftCollection $shiftCollection, array $userIdList): ShiftStatisticMap
	{
		$statisticMap = new ShiftStatisticMap();
		$statisticMap->fill($userIdList);

		foreach ($shiftCollection as $shift)
		{
			$statistic = $statisticMap->get($shift->getUserId());

			$statistic?->increaseShiftCounter();
		}

		return $statisticMap;
	}
}