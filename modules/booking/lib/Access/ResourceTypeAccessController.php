<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access;

use Bitrix\Booking\Access\Model\ResourceType;
use Bitrix\Booking\Access\Model\User;
use Bitrix\Booking\Internals\Container;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;

class ResourceTypeAccessController extends BaseAccessController
{
	public static array $cache = [];

	private const ITEM_TYPE = 'RESOURCE_TYPE';
	private const USER_TYPE = 'USER';

	public static function can($userId, string|ResourceTypeAction $action, $itemId = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;

		return parent::can($userId, $action, $itemId, $params);
	}

	public function check(string|ResourceTypeAction $action, AccessibleItem $item = null, $params = null): bool
	{
		$action = is_string($action) ? $action : $action->value;

		return parent::check($action, $item, $params);
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		$key = self::ITEM_TYPE . '_' . $itemId;

		if (!array_key_exists($key, self::$cache))
		{
			$resourceTypeAccessModel = ResourceType::createFromDomainObject(
				resourceType: Container::getResourceTypeRepository()->getById($itemId),
			);

			self::$cache[$key] = $resourceTypeAccessModel;
		}

		return self::$cache[$key];
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		$key = self::USER_TYPE . '_' . $userId;

		if (!array_key_exists($key, self::$cache))
		{
			self::$cache[$key] = User::createFromId($userId);
		}

		return self::$cache[$key];
	}
}
