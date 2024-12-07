<?php

namespace Bitrix\StaffTrack\Service;

use Bitrix\StaffTrack\Item\User;
use Bitrix\StaffTrack\Provider\UserProvider;
use Bitrix\StaffTrack\Trait\Singleton;

class UserService
{
	use Singleton;

	public function checkUser(int $userId, string $hash): bool
	{
		$user = $this->getUser($userId);
		if ($user === null)
		{
			return false;
		}

		return $user->hash === $hash;
	}

	public function getUser(int $userId): ?User
	{
		return UserProvider::getInstance()->getUser($userId);
	}
}