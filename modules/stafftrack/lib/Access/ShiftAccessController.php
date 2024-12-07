<?php

namespace Bitrix\StaffTrack\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\StaffTrack\Access\Model\ShiftModel;
use Bitrix\StaffTrack\Access\Model\UserModel;
use Bitrix\StaffTrack\Shift\ShiftRegistry;

class ShiftAccessController extends BaseAccessController
{
	public static array $cache = [];

	private const ITEM_TYPE = 'SHIFT';
	private const USER_TYPE = 'USER';

	/**
	 * @param int|null $itemId
	 * @return AccessibleItem|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		$key = self::ITEM_TYPE . '_' . $itemId;
		if (!isset(static::$cache[$key]))
		{
			$shift = ShiftRegistry::getInstance()->get($itemId);

			if ($shift !== null)
			{
				$shiftModel = ShiftModel::createFromObject($shift);
			}
			else
			{
				$shiftModel = ShiftModel::createNew();
			}

			static::$cache[$key] = $shiftModel;
		}

		return static::$cache[$key];
	}

	/**
	 * @param int $userId
	 * @return AccessibleUser
	 */
	protected function loadUser(int $userId): AccessibleUser
	{
		$key = self::USER_TYPE . '_' . $userId;
		if (!array_key_exists($key, static::$cache))
		{
			static::$cache[$key] = UserModel::createFromId($userId);
		}

		return static::$cache[$key];
	}
}