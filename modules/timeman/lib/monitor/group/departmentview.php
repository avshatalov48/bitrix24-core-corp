<?php
namespace Bitrix\Timeman\Monitor\Group;

use Bitrix\Timeman\Monitor\Utils\Department;

class DepartmentView
{
	protected const DEFAULT_USER_ID = 0;

	public static function addSiteForCurrentUserDepartments(array $siteIds): bool
	{
		global $USER;
		$departments = Department::getUserDepartments($USER->getId());

		$values = [];
		foreach ($departments as $department)
		{
			foreach ($siteIds as $id)
			{
				$values[] = [
					'DEPARTMENT_ID' => (int)$department,
					'ENTITY_TYPE' => EntityType::SITE,
					'ENTITY_ID' => (int)$id
				];
			}
		}

		return self::add($values);
	}

	public static function addAppForCurrentUserDepartments(array $appIds): bool
	{
		global $USER;
		$departments = Department::getUserDepartments($USER->getId());

		$values = [];
		foreach ($departments as $department)
		{
			foreach ($appIds as $id)
			{
				$values[] = [
					'DEPARTMENT_ID' => (int)$department,
					'ENTITY_TYPE' => EntityType::APP,
					'ENTITY_ID' => (int)$id
				];
			}
		}

		return self::add($values);
	}

	protected static function add($values): bool
	{
		global $DB;

		$queryBase = "
				INSERT IGNORE INTO b_timeman_monitor_group_access
				(DEPARTMENT_ID, ENTITY_TYPE, ENTITY_ID, GROUP_CODE, CREATED_USER_ID, DATE_CREATE)
				VALUES
		";

		$queryValues = "";
		$maxValuesLength = 2048;

		foreach($values as $value)
		{
			$queryValues .= ",\n(".(int)$value['DEPARTMENT_ID'].
							", '".$DB->ForSql($value['ENTITY_TYPE']).
							"', ".(int)$value['ENTITY_ID'].
							", '".$DB->ForSql(Group::CODE_OTHER).
							"', ".self::DEFAULT_USER_ID.
							", now())";

			if(mb_strlen($queryValues) > $maxValuesLength)
			{
				$query = $queryBase . mb_substr($queryValues, 2);
				$DB->Query($query, false);
				$queryValues = "";
			}
		}

		if($queryValues !== "")
		{
			$query = $queryBase . mb_substr($queryValues, 2);
			$DB->Query($query, false);
		}

		return true;
	}
}