<?php

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Tasks\Access\Model\TagModel;

class TagAccessController extends BaseAccessController implements AccessErrorable
{
	use AccessUserTrait;
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

	public static function invalidate(?int $tagId = null): void
	{
		if (is_null($tagId))
		{
			static::$cache = [];
		}
		else
		{
			$key = 'TAG_' . $tagId;
			unset(static::$cache[$key]);
		}

		TagModel::invalidate($tagId);
	}
}