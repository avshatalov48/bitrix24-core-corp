<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Control\Trait;

use Bitrix\Tasks\Flow\Provider\UserProvider;
use Bitrix\Tasks\Util\User;

trait ActiveUserOrAdminTrait
{
	public function getActiveUserOrAdminId(int $userId): int
	{
		return User::isActive($userId)
			? $userId
			: UserProvider::getDefaultAdminId()
		;
	}
}
