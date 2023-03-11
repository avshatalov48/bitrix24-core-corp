<?php

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\Model\TagModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Internals\Log\Log;

class TagAccessController extends BaseAccessController implements AccessErrorable
{
	use AccessErrorTrait;

	public static array $cache = [];

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		$key = 'TAG_' . $itemId;
		if (!array_key_exists($key, static::$cache))
		{
			static::$cache[$key] = TagModel::createFromId($itemId);
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

	public static function dropItemCache(int $itemId): void
	{
		$key = 'TAG_' . $itemId;
		unset(static::$cache[$key]);

		TagModel::invalidateCache($itemId);
	}
}