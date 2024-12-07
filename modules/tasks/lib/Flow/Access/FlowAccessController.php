<?php

namespace Bitrix\Tasks\Flow\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Tasks\Access\AccessErrorable;
use Bitrix\Tasks\Access\AccessErrorTrait;
use Bitrix\Tasks\Access\AccessUserTrait;

class FlowAccessController extends BaseAccessController implements AccessErrorable
{
	use AccessUserTrait;
	use AccessErrorTrait;
	protected static array $cache = [];

	public static function can($userId, string|FlowAction $action, $itemId = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;
		return parent::can($userId, $action, $itemId, $params);
	}

	public function check(string|FlowAction $action, AccessibleItem $item = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;
		return parent::check($action, $item, $params);
	}

	protected function loadItem(int $itemId = null): ?FlowModel
	{
		$itemId = (int)$itemId;
		if ($itemId === 0)
		{
			return new FlowModel();
		}

		$key = 'FLOW_' . $itemId;
		if (!isset(static::$cache[$key]))
		{
			static::$cache[$key] = FlowModel::createFromId($itemId);
		}

		return static::$cache[$key];
	}
}