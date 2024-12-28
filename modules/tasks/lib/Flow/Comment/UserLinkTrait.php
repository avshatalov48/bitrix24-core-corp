<?php

namespace Bitrix\Tasks\Flow\Comment;

use Bitrix\Tasks\Util\User;

trait UserLinkTrait
{
	protected static array $userNames = [];

	protected function getUserBBCodes(mixed $userIds): string
	{
		if (!is_array($userIds))
		{
			$userIds = [$userIds];
		}

		$userNames = $this->getUserNames($userIds);

		$result = '';
		foreach ($userNames as $userId => $userName)
		{
			$result .= "[USER={$userId}]{$userName}[/USER], ";
		}

		return rtrim($result, ', ');
	}

	protected function getUserNames(array $users): array
	{
		if (empty($users))
		{
			return [];
		}

		$usersToFind = array_flip(array_diff_key(array_flip($users), static::$userNames));

		if (!empty($usersToFind))
		{
			$userNames = User::getUserName($usersToFind);
			foreach ($userNames as $userId => $userName)
			{
				static::$userNames[$userId] = $userName;
			}
		}

		return array_intersect_key(static::$userNames, array_flip($users));
	}
}