<?php

namespace Bitrix\Sign\Integration\Main;

use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Sign\Service\Container;

class SignersListEventHandler
{
	private const FALLBACK_ADMIN_USER_ID = 1;

	public static function OnAfterUserUpdate(array $data): void
	{
		if (
			isset($data['ACTIVE'], $data['ID'])
			&& $data['ACTIVE'] === 'N'
		)
		{
			self::deleteUserFromAllLists((int)$data['ID']);
		}
	}

	public static function OnAfterUserDelete(int $userId): void
	{
		self::deleteUserFromAllLists($userId);
	}

	private static function deleteUserFromAllLists(int $userId): void
	{
		$modifiedById = self::getAdminUserId();
		Container::instance()->getSignersListService()->deleteUserFromAllLists($userId, $modifiedById);
	}

	private static function getAdminUserId(): int
	{
		if (!Loader::includeModule('intranet'))
		{
			return self::FALLBACK_ADMIN_USER_ID;
		}

		$admins = ServiceContainer::getInstance()->getUserService()->getAdminUserIds();
		return !empty($admins) ? (int)reset($admins) : self::FALLBACK_ADMIN_USER_ID;
	}
}
