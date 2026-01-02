<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\Main\UserTable;

class AccessCodeUpdate
{
	private const LIMIT = 100;

	public static function run(int $lastUserId = 0): string
	{
		$users = UserTable::query()
			->setSelect([
				'ID',
				'UF_DEPARTMENT',
			])
			->setOffset($lastUserId)
			->setLimit(self::LIMIT)
			->setOrder(['ID' => 'ASC'])
			->where('ACTIVE', 'Y')
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

			\CAccess::RecalculateForUser($userId, 'intranet');
			$access = new \CAccess;
			$access->updateCodes([
				'USER_ID' => $userId,
			]);
		}

		$lastUserId += self::LIMIT;

		return "\\Bitrix\\HumanResources\\Install\\Agent\\AccessCodeUpdate::run($lastUserId);";
	}
}