<?php

namespace Bitrix\HumanResources\Compatibility\Utils;

class DepartmentBackwardAccessCode
{
	public static function makeById(int $departmentId): string
	{
		return 'D' . $departmentId;
	}

	/**
	 * @deprecated Use \Bitrix\HumanResources\Contract\Repository\NodeRepository::getByAccessCode to get node item.
	 * This method extracts the id only for departments that are exists in the old iblock structure.
	 *
	 * @param string|null $accessCode
	 *
	 * @return int|null
	 */
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