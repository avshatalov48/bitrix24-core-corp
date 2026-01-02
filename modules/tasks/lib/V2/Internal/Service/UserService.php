<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity\User;

class UserService
{
	public function isEmail(User $user): bool
	{
		if(!Loader::includeModule('mail'))
		{
			return false;
		}

		return $user->externalAuthId === 'email';
	}
}