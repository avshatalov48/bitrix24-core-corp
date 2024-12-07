<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\UserTable;

class SyncInActiveUsers
{
	private const LIMIT = 200;

	public static function run(int $lastUserId = 0): string
	{
		$userCollection = UserTable::query()
			->setSelect(['ID'])
			->where('ACTIVE', '=', 'N')
			->setOffset($lastUserId)
			->setLimit(self::LIMIT)
			->fetchCollection()
		;

		$userIds = [];
		foreach ($userCollection as $user)
		{
			$userIds[] = $user->getId();
		}

		if (empty($userIds))
		{
			return self::finish();
		}

		$result = Container::getNodeMemberRepository()
			->setActiveByEntityTypeAndEntityIds(
				entityType: MemberEntityType::USER,
				entityIds: $userIds,
				active: false,
			)
		;

		$nextLastUserId = $result->isSuccess()
			? $lastUserId + self::LIMIT
			: $lastUserId
		;

		return "\\Bitrix\\HumanResources\\Install\\Agent\\SyncInActiveUsers::run($nextLastUserId);";
	}

	private static function finish(): string
	{
		\Bitrix\HumanResources\Compatibility\Adapter\StructureBackwardAdapter::clearCache();

		return '';
	}
}