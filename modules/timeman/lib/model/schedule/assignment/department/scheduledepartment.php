<?php
namespace Bitrix\Timeman\Model\Schedule\Assignment\Department;

use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Contract\ScheduleAssignable;

class ScheduleDepartment extends EO_ScheduleDepartment implements ScheduleAssignable
{
	public static function create($scheduleId, $departmentId, $excluded = false)
	{
		$item = new static($defaultValues = false);
		$item->setScheduleId($scheduleId);
		$item->setDepartmentId($departmentId);
		if ($excluded)
		{
			$item->setIsExcluded();
		}
		else
		{
			$item->setIsIncluded();
		}
		return $item;
	}

	public static function isDepartmentExcluded($departmentAssignment)
	{
		return $departmentAssignment['STATUS'] == ScheduleDepartmentTable::EXCLUDED;
	}

	public static function isDepartmentIncluded($departmentAssignment)
	{
		return $departmentAssignment['STATUS'] == ScheduleDepartmentTable::INCLUDED;
	}

	public function isExcluded()
	{
		return static::isDepartmentExcluded($this);
	}

	public function setIsExcluded()
	{
		$this->setStatus(ScheduleDepartmentTable::EXCLUDED);
		return $this;
	}

	public function setIsIncluded()
	{
		$this->setStatus(ScheduleDepartmentTable::INCLUDED);
		return $this;
	}

	public function isIncluded()
	{
		return static::isDepartmentIncluded($this);
	}

	public function getEntityCode()
	{
		return EntityCodesHelper::buildDepartmentCode($this->getDepartmentId());
	}
}