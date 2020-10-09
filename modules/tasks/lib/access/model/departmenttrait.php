<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Tasks\Access\Role\RoleDictionary;

trait DepartmentTrait
{
	private static $cache = [];

	public function getDepartments(array $roles = []): array
	{
		$key = 'DEP_' . static::class . '_' . $this->getId() . '_' . implode(',', $roles);

		if (!array_key_exists($key, static::$cache))
		{
			$members = $this->getMembers();

			$userIds = [];

			foreach ($members as $role => $ids)
			{
				if (
					empty($roles)
					|| in_array($role, $roles)
				)
				{
					$userIds = array_merge($userIds, $ids);
				}
			}

			static::$cache[$key] = [];
			if (!empty($userIds))
			{
				$userIds = implode(',', $userIds);

				$res = \Bitrix\Tasks\Util\User::getList(
					[
						'filter' => [
							'@ID' => new SqlExpression($userIds),
						],
						'select' => ['ID', 'UF_DEPARTMENT']
					]
				);

				foreach ($res as $row)
				{
					if (is_array($row['UF_DEPARTMENT']) && !empty($row['UF_DEPARTMENT']))
					{
						static::$cache[$key] = array_merge(static::$cache[$key], $row['UF_DEPARTMENT']);
					}
				}
			}
		}
		return static::$cache[$key];
	}

	public function isInDepartment(int $userId, bool $recursive = false, array $roles = []): bool
	{
		$userDepartments = \CIntranetUtils::GetUserDepartments($userId);
		if (!is_array($userDepartments))
		{
			return false;
		}
		return !empty(array_intersect($userDepartments, $this->getDepartments($roles)));
	}

}