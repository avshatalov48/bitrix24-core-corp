<?php

namespace Bitrix\HumanResources\Compatibility\Utils;

class DepartmentBackwardAccessCode
{
	public static function makeById(int $departmentId): string
	{
		return 'D' . $departmentId;
	}

	public static function extractIdFromCode(?string $accessCode): ?int
	{
		if (empty($accessCode))
		{
			return null;
		}

		if (preg_match('/^(D)(\d+)$/', $accessCode, $matches))
		{
			if (array_key_exists('2', $matches))
			{
				return (int) $matches[2];
			}
		}

		return null;
	}
}