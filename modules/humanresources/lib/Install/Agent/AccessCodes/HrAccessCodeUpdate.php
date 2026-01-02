<?php

namespace Bitrix\HumanResources\Install\Agent\AccessCodes;

use Bitrix\Main\UserTable;

class HrAccessCodeUpdate
{
	private const LIMIT = 50;

	public static function run(int $lastUserId = 0): string
	{
		$users = UserTable::query()
			->setSelect([
				'ID',
				'UF_DEPARTMENT',
			])
			->setLimit(self::LIMIT)
			->setOrder(['ID' => 'ASC'])
			->where('UF_DEPARTMENT', '!=', false)
			->where('ID', '>', $lastUserId )
			->fetchAll()
		;

		if (empty($users))
		{
			return '';
		}

		foreach ($users as $user)
		{
			if (empty($user['UF_DEPARTMENT']))
			{
				continue;
			}
			$userId = (int)$user['ID'];

			\CAccess::RecalculateForUser($userId, 'hr_structure');
			\CAccess::RecalculateForUser($userId, 'access');
			$access = new \CAccess;
			$access->updateCodes([
				'USER_ID' => $userId,
			]);
		}

		$userId = (int)$user['ID'];
		if (!$userId)
		{
			$userId = $lastUserId + self::LIMIT;
		}

		$lastUserId = $userId;

		return "Bitrix\\HumanResources\\Install\\Agent\\AccessCodes\\HrAccessCodeUpdate::run($lastUserId);";
	}
}