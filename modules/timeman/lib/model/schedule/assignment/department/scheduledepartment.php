<?php
namespace Bitrix\Timeman\Model\Schedule\Assignment\Department;

class ScheduleDepartment extends EO_ScheduleDepartment
{
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

	public function isIncluded()
	{
		return static::isDepartmentIncluded($this);
	}
}