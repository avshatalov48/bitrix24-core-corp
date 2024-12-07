<?php

namespace Bitrix\Tasks\Flow\Comment;

use Bitrix\Tasks\Util\User;

trait UserLinkTrait
{
	protected static array $userNames = [];

	protected function parseUserToLinked(int $userId): string
	{
		$userName = $this->getUserNames([$userId])[$userId];
		return "[USER={$userId}]{$userName}[/USER]";
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