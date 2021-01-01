<?php
namespace Bitrix\Timeman\Monitor\Group;

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Timeman\Model\Monitor\MonitorGroupAccessTable;
use Bitrix\Timeman\Monitor\Utils\Department;

class GroupAccess
{
	public static function moveSiteToGroupForDepartment(int $siteId, string $groupCode, int $departmentId, bool $recursively = false)
	{
		return self::moveEntityToGroupForDepartment(EntityType::SITE, $siteId, $groupCode, $departmentId, $recursively);
	}

	public static function moveAppToGroupForDepartment(int $appId, string $groupCode, int $departmentId, bool $recursively = false)
	{
		return self::moveEntityToGroupForDepartment(EntityType::APP, $appId, $groupCode, $departmentId, $recursively);
	}

	private static function moveEntityToGroupForDepartment(string $entityType, int $entityId, string $groupCode, int $departmentId, bool $recursively)
	{
		global $USER;

		if (!$recursively)
		{
			return MonitorGroupAccessTable::merge([
				'GROUP_CODE' => $groupCode,
				'DEPARTMENT_ID' => $departmentId,
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId,
				'CREATED_USER_ID' => $USER->GetID(),
				'DATE_CREATE' => new DateTime()
			])->isSuccess();
		}

		$departmentIds = array_merge([$departmentId], Department::getSubordinateDepartments($departmentId));
		$existingDepartmentIds = self::getDepartmentIdsWithExistingAccess($entityType, $entityId, $departmentIds);
		$departmentIdsToInsert = array_diff($departmentIds, $existingDepartmentIds);

		if ($departmentIdsToInsert)
		{
			$valuesToInsert = [];
			foreach ($departmentIdsToInsert as $id)
			{
				$valuesToInsert[] = [
					'DEPARTMENT_ID' => (int)$id,
					'ENTITY_TYPE' => $entityType,
					'ENTITY_ID' => $entityId,
					'GROUP_CODE' => $groupCode,
					'CREATED_USER_ID' => $USER->GetID(),
				];
			}

			self::addAccesses($valuesToInsert);
		}

		self::updateGroupCodeForDepartments($entityType, $entityId, $groupCode, $departmentIds);

		return true;
	}

	private static function addAccesses($values): bool
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
							", '".$DB->ForSql($value['GROUP_CODE']).
							"', ".(int)$value['CREATED_USER_ID'].
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

	private static function updateGroupCodeForDepartments(string $entityType, int $entityId, string $groupCode, array $departmentIds): bool
	{
		global $DB;

		$update = "UPDATE b_timeman_monitor_group_access SET GROUP_CODE = '{$DB->forSql($groupCode)}' ";
		$where = "WHERE ";
		$where .= "ENTITY_TYPE = '{$DB->forSql($entityType)}' AND ";
		$where .= "ENTITY_ID = {$entityId} AND ";
		$where .= "DEPARTMENT_ID IN (";
		foreach ($departmentIds as $departmentId)
		{
			$where .= (int)$departmentId . ', ';
		}
		$where = mb_substr($where, 0, -2);
		$where .= ");";

		$query = $update . $where;
		$DB->Query($query, false);

		return true;
	}

	private static function getDepartmentIdsWithExistingAccess(string $entityType, int $entityId, array $departments)
	{
		$query = MonitorGroupAccessTable::query();
		$query->addSelect('DEPARTMENT_ID');
		$query->where('ENTITY_TYPE', '=', $entityType);
		$query->where('ENTITY_ID', '=', $entityId);
		$query->whereIn('DEPARTMENT_ID', $departments);
		$existingDepartments = $query->exec()->fetchAll();

		$existingDepartmentIds = [];
		foreach ($existingDepartments as $existingDepartment)
		{
			$existingDepartmentIds[] = $existingDepartment['DEPARTMENT_ID'];
		}

		return $existingDepartmentIds;
	}

	public static function getByDepartment(int $departmentId): array
	{
		$query = MonitorGroupAccessTable::query();

		$query->setSelect([
			'GROUP_CODE',
			'DEPARTMENT_ID',
			'ENTITY_TYPE',
			'ENTITY_ID',
			'CREATED_USER_ID',
			'CREATED_USER_NAME',
			'DATE_CREATE',
		]);

		$query->where('DEPARTMENT_ID', '=', $departmentId);

		$userQuery = UserTable::query();
		$userQuery->setSelect([
			'ID',
			'NAME',
			'LAST_NAME'
		]);

		$query->registerRuntimeField(new ReferenceField(
			'user',
			Base::getInstanceByQuery($userQuery),
			Join::on('this.CREATED_USER_ID', 'ref.ID')
		));

		$query->registerRuntimeField(new ExpressionField(
			'CREATED_USER_NAME',
			"concat(%s, ' ', %s)",
			['user.NAME', 'user.LAST_NAME']
		));

		$accesses = $query->exec()->fetchAll();

		$group = [];
		foreach ($accesses as $access)
		{
			$group[$access['ENTITY_TYPE']][$access['GROUP_CODE']][] = [
				'DEPARTMENT_ID' => (int)$access['DEPARTMENT_ID'],
				'GROUP' => $access['GROUP_CODE'],
				'ID' => (int)$access['ENTITY_ID'],
				'CREATED_USER_ID' => (int)$access['CREATED_USER_ID'],
				'CREATED_USER_NAME' => $access['CREATED_USER_NAME'],
				'DATE_CREATE' => $access['DATE_CREATE'],
			];
		}

		return $group;
	}

	public static function getByDepartments($departmentIds): array
	{
		$query = MonitorGroupAccessTable::query();

		$query->setSelect([
			'GROUP_CODE',
			'DEPARTMENT_ID',
			'ENTITY_TYPE',
			'ENTITY_ID',
			'CREATED_USER_ID',
			'CREATED_USER_NAME',
			'DATE_CREATE',
		]);

		$query->whereIn('DEPARTMENT_ID', $departmentIds);

		$userQuery = UserTable::query();
		$userQuery->setSelect([
			'ID',
			'NAME',
			'LAST_NAME'
		]);

		$query->registerRuntimeField(new ReferenceField(
			'user',
			Base::getInstanceByQuery($userQuery),
			Join::on('this.CREATED_USER_ID', 'ref.ID')
		));

		$query->registerRuntimeField(new ExpressionField(
			'CREATED_USER_NAME',
			"concat(%s, ' ', %s)",
			['user.NAME', 'user.LAST_NAME']
		));

		$unsortedGroups = $query->exec()->fetchAll();

		$groups = [];
		foreach ($unsortedGroups as $group)
		{
			foreach ($departmentIds as $departmentId)
			{
				if ((int)$group['DEPARTMENT_ID'] === $departmentId)
				{
					$groups[$group['ENTITY_TYPE']][] = [
						'DEPARTMENT_ID' => $departmentId,
						'GROUP_CODE' => $group['GROUP_CODE'],
						'ID' => $group['ENTITY_ID'],
						'CREATED_USER_ID' => $group['CREATED_USER_ID'],
						'CREATED_USER_NAME' => $group['CREATED_USER_NAME'],
						'DATE_CREATE' => $group['DATE_CREATE'],
					];

					break;
				}
			}
		}

		return self::imposeGroups($groups);
	}

	protected static function imposeGroups($groups): array
	{
		$departmentGroup = [];
		foreach ($groups as $entityType => $accesses)
		{
			if (!$departmentGroup[$entityType])
			{
				$departmentGroup[$entityType] = [];
			}

			foreach ($accesses as $access)
			{
				$departmentGroup[$entityType][$access['ID']] = [
					'GROUP_CODE' => $access['GROUP_CODE'],
					'CREATED_USER_ID' => $access['CREATED_USER_ID'],
					'CREATED_USER_NAME' => $access['CREATED_USER_NAME'],
					'DATE_CREATE' => $access['DATE_CREATE']
				];
			}
		}

		return $departmentGroup;
	}
}