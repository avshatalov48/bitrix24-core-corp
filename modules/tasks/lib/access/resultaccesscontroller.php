<?php

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Model\ResultModel;
use Bitrix\Tasks\Access\Model\UserModel;

class ResultAccessController extends BaseAccessController implements AccessErrorable
{
	use AccessErrorTrait;

	public static array $cache = [];

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		$key = 'RESULT_' . $itemId;
		if (!array_key_exists($key, static::$cache))
		{
			static::$cache[$key] = ResultModel::createFromId($itemId);
		}

		return static::$cache[$key];
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		$key = 'USER_' . $userId;
		if (!array_key_exists($key, static::$cache))
		{
			static::$cache[$key] = UserModel::createFromId($userId);
		}

		return static::$cache[$key];
	}
}