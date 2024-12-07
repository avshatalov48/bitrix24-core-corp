<?php

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Model\UserModel;

trait AccessUserTrait
{
	protected function loadUser(int $userId): AccessibleUser
	{
		$key = 'USER_'.$userId;
		if (!array_key_exists($key, static::$cache))
		{
			static::$cache[$key] = UserModel::createFromId($userId);
		}
		return static::$cache[$key];
	}
}