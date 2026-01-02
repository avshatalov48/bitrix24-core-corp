<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Socialnetwork\Service;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Permission\OperationService;
use CAllSocNetUser;

class OperationAccessService
{
	public function filterUsersWhoCanViewProfile(int $userId, array $targetUserIds): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		$usersWithAccess = [];
		foreach ($targetUserIds as $targetUserId)
		{
			if ($this->canViewProfile($userId, $targetUserId))
			{
				$usersWithAccess[] = $targetUserId;
			}
		}

		return $usersWithAccess;
	}

	public function canViewProfile(int $userId, int $targetUserId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		return CAllSocNetUser::CanProfileView($userId, $targetUserId);
	}

	public function filterUsersWithAccess(
		int    $groupId,
		array  $users,
		string $type,
		string $feature,
		string $operation,
		bool   $isAdmin = false
	): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		return (new OperationService())->filterUsersWithAccess(
			groupId: $groupId,
			users: $users,
			type: $type,
			feature: $feature,
			operation: $operation,
			isAdmin: $isAdmin
		);
	}
}
