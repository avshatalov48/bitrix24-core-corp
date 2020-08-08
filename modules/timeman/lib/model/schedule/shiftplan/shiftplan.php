<?php
namespace Bitrix\Timeman\Model\Schedule\ShiftPlan;

use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;

class ShiftPlan extends EO_ShiftPlan
{
	/** @var Schedule|null */
	private $schedule;

	public static function create(ShiftPlanForm $shiftPlanForm)
	{
		$shiftPlan = new static($default = false);
		$shiftPlan->setUserId($shiftPlanForm->userId);
		$shiftPlan->setShiftId($shiftPlanForm->shiftId);
		$shiftPlan->setDateAssigned($shiftPlanForm->getDateAssigned());
		$shiftPlan->setDeleted(ShiftPlanTable::DELETED_NO);
		$shiftPlan->setCreatedAt(TimeHelper::getInstance()->getUtcNowTimestamp());
		return $shiftPlan;
	}

	public function markDeleted()
	{
		$this->setDeleted(ShiftPlanTable::DELETED_YES);
		$this->setDeletedAt(TimeHelper::getInstance()->getUtcNowTimestamp());
	}

	public function restore()
	{
		$this->setDeleted(ShiftPlanTable::DELETED_NO);
		$this->setDeletedAt(0);
	}

	public function isActive()
	{
		return !$this->isDeleted();
	}

	public function isDeleted()
	{
		return $this->getDeleted();
	}

	public function getDateAssignedUtcFormatted()
	{
		if (!$this->getDateAssignedUtc())
		{
			return '';
		}
		return $this->getDateAssignedUtc()->format(ShiftPlanTable::DATE_FORMAT);
	}

	public function getDateAssignedTimestamp()
	{
		if (!$this->getDateAssignedUtc())
		{
			return null;
		}
		return $this->getDateAssignedUtc()->getTimestamp();
	}

	public function getDateAssignedUtc()
	{
		if (!$this->getDateAssigned())
		{
			return null;
		}
		return \DateTime::createFromFormat('Y-m-d H i s', $this->getDateAssigned()->format('Y-m-d') . ' 00 00 00', new \DateTimeZone('UTC'));
	}

	/**
	 * @return Shift|null
	 */
	public function obtainShift()
	{
		try
		{
			return $this->get('SHIFT') ? $this->get('SHIFT') : null;
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	public function buildShiftStartDateTimeUtc(Shift $shift)
	{
		return $shift->buildUtcStartByShiftplan($this);
	}

	public function obtainSchedule()
	{
		if ($this->schedule instanceof Schedule)
		{
			return $this->schedule;
		}
		try
		{
			return $this->get('SCHEDULE') ? $this->get('SCHEDULE') : null;
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	public function defineSchedule(?Schedule $schedule)
	{
		$this->schedule = $schedule;
		return $this;
	}
}