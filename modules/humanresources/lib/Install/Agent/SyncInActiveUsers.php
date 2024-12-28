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
			->setSelect(['ID', 'ACTIVE'])
			->setOffset($lastUserId)
			->setLimit(self::LIMIT)
			->setOrder(['ID' => 'ASC'])
			->fetchCollection()
		;

		$activeUsers = [];
		$inactiveUsers = [];
		foreach ($userCollection as $user)
		{
			if ($user->getActive())
			{
				$activeUsers[] = $user->getId();
			}
			else
			{
				$inactiveUsers[] = $user->getId();
			}
		}

		if (empty($activeUsers) && empty($inactiveUsers))
		{
			return self::finish();
		}

		Container::getNodeMemberRepository()
			->setActiveByEntityTypeAndEntityIds(
				entityType: MemberEntityType::USER,
				entityIds: $inactiveUsers,
				active: false,
			)
		;

		$result = Container::getNodeMemberRepository()
			->setActiveByEntityTypeAndEntityIds(
				entityType: MemberEntityType::USER,
				entityIds: $activeUsers,
				active: true,
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