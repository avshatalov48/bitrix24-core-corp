<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Main\Access\AccessibleItem;

class TaskAccessController extends BaseAccessController
	implements AccessErrorable
{

	use AccessUserTrait;
	use AccessErrorTrait;

	public static $cache = [];

	public function check(string $action, AccessibleItem $item = null, $params = null): bool
	{
		if (!$item)
		{
			$item = TaskModel::createNew();
		}

		if ($item->isDeleted())
		{
			return false;
		}

		return parent::check($action, $item, $params);
	}

	public static function dropItemCache(int $itemId)
	{
		$key = 'TASK_'.$itemId;
		unset(static::$cache[$key]);

		\Bitrix\Tasks\Access\Model\TaskModel::invalidateCache($itemId);
	}

	protected function loadItem(int $itemId = null): AccessibleItem
	{
		if (!$itemId)
		{
			return TaskModel::createNew();
		}

		$key = 'TASK_'.$itemId;
		if (!array_key_exists($key, static::$cache))
		{
			static::$cache[$key] = TaskModel::createFromId($itemId);
		}
		return static::$cache[$key];
	}
}