<?php
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan;

use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;

class ShiftPlan extends EO_ShiftPlan
{
	public static function create(ShiftPlanForm $shiftPlanForm)
	{
		$entity = new static($default = false);
		$entity->setUserId($shiftPlanForm->userId);
		$entity->setShiftId($shiftPlanForm->shiftId);
		$entity->setDateAssigned($shiftPlanForm->getDateAssigned());
		return $entity;
	}

	/**
	 * @return Schedule|null
	 */
	public function obtainSchedule()
	{
		try
		{
			return $this->get('SCHEDULE');
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	/**
	 * @return Shift|null
	 */
	public function obtainShift()
	{
		try
		{
			return $this->get('SHIFT');
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}
}