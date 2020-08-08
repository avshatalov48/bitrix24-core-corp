<?php
namespace Bitrix\Timeman\Provider\Schedule;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;

class ShiftPlanProvider extends ShiftPlanRepository
{
	private $activePlansByComplexId = [];

	public function findActiveByComplexId($shiftId, $userId, $dateAssigned)
	{
		if (!($dateAssigned instanceof Date))
		{
			return null;
		}
		$key = $this->buildComplexIdKey($shiftId, $userId, $dateAssigned);
		if ($this->activePlansByComplexId[$key] === null)
		{
			$this->activePlansByComplexId[$key] = false;
			$result = parent::findActiveByComplexId($shiftId, $userId, $dateAssigned);
			if ($result !== null)
			{
				$this->activePlansByComplexId[$key] = $result;
			}
		}
		return $this->activePlansByComplexId[$key] === false ? null : $this->activePlansByComplexId[$key];
	}

	private function buildComplexIdKey($shiftId, $userId, Date $dateAssigned)
	{
		return $shiftId . '-' . $userId . '-' . $dateAssigned->format('Y-m-d');
	}
}