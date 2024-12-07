<?php

namespace Bitrix\Tasks\Integration\Intranet\Flow;

use Bitrix\Main\Loader;
use CIntranetUtils;

class Department
{
	public static function getSubDepartments(int $departmentId): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		return CIntranetUtils::GetIBlockSectionChildren($departmentId) ?? [];
	}

	public static function getDepartmentsData(int ...$departmentIds): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		return CIntranetUtils::GetDepartmentsData($departmentIds);
	}
}