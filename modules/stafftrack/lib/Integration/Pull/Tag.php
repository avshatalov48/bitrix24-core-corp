<?php

namespace Bitrix\Stafftrack\Integration\Pull;

class Tag
{
	public static function getDepartmentTag(int $departmentId): string
	{
		return "stafftrack-department-$departmentId";
	}

	public static function getUserTag(int $userId): string
	{
		return "stafftrack-user-$userId";
	}
}