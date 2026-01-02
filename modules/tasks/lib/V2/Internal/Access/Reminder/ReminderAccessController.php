<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Reminder;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Tasks\Access\AccessErrorTrait;
use Bitrix\Tasks\Access\AccessUserTrait;

class ReminderAccessController extends BaseAccessController
{
	use AccessUserTrait;
	use AccessErrorTrait;

	private static array $cache = [];

	public static function can($userId, string|ReminderAction $action, $itemId = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;
		return parent::can($userId, $action, $itemId, $params);
	}

	public function check(string|ReminderAction $action, AccessibleItem $item = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;
		return parent::check($action, $item, $params);
	}

	public function checkByItemId(string|ReminderAction $action, int $itemId = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;

		return parent::checkByItemId($action, $itemId, $params);
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		$itemId = (int)$itemId;
		if ($itemId === 0)
		{
			return new ReminderModel();
		}

		$key = 'REMINDER_' . $itemId;
		if (!isset(static::$cache[$key]))
		{
			static::$cache[$key] = ReminderModel::createFromId($itemId);
		}

		return static::$cache[$key];
	}
}