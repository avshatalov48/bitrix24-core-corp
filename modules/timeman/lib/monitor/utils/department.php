<?php
namespace Bitrix\Timeman\Monitor\Utils;

class Department
{
	public static function getUserDepartments(int $userId): array
	{
		$structure = \CIntranetUtils::GetStructure();

		$departments = [];
		foreach ($structure['DATA'] as $department)
		{
			if ((string)$userId === $department['UF_HEAD'] || in_array($userId, $department['EMPLOYEES']))
			{
				$departments[] = (int)$department['ID'];
			}
		}

		return $departments;
	}

	public static function getPathFromHeadToDepartment($departmentId): array
	{
		$departmentTree = \CIntranetUtils::GetDeparmentsTree(0);

		$path[] = $departmentId;
		while ($departmentId !== 0)
		{
			$departmentId = self::getHeadDepartmentId($departmentId, $departmentTree);
			$path[] = $departmentId;
		}

		return array_reverse($path);
	}

	protected static function getHeadDepartmentId(int $departmentId, array $departmentTree)
	{
		foreach ($departmentTree as $headDepartmentId => $subDepartments)
		{
			foreach ($subDepartments as $subDepartmentId)
			{
				if ((int)$subDepartmentId === $departmentId)
				{
					return $headDepartmentId;
				}
			}
		}

		return null;
	}

	public static function getSubordinateDepartments(int $departmentId): array
	{
		return \CIntranetUtils::GetDeparmentsTree($departmentId, true);
	}

	public static function getDepartmentsEmployees(array $departmentIds, bool $recursive = false): array
	{
		$departmentsEmployees = [];

		$users = \CIntranetUtils::getDepartmentEmployees($departmentIds, $recursive);

		while ($user = $users->Fetch())
		{
			$departmentsEmployees[] = (int)$user['ID'];
		}

		return $departmentsEmployees;
	}
}