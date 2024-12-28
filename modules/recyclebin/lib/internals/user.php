<?php

namespace Bitrix\Recyclebin\Internals;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class User
{
	/**
	 * Returns current user ID
	 * @return integer
	 */
	public static function getCurrentUserId()
	{
		global $USER;

		if (is_object($USER) && method_exists($USER, 'getId'))
		{
			$userId = $USER->getId();
			if ($userId > 0)
			{
				return $userId;
			}
		}

		return 0;
	}

	public static function isSuper($userId = 0)
	{
		return static::isAdmin($userId) || \Bitrix\Recyclebin\Integration\Bitrix24\User::isAdmin($userId);
	}

	/**
	 * Check if a user with a given id is admin
	 *
	 * @param 0 $userId
	 *
	 * @return bool
	 */
	public static function isAdmin($userId = 0)
	{
		global $USER;

		static $users = array();

		if ($userId === 0 || $userId === false)
		{
			$userId = null;
		}

		$isAdmin = false;
		$loggedInUserId = null;

		if ($userId === null)
		{
			if (is_object($USER) && method_exists($USER, 'GetID'))
			{
				$loggedInUserId = (int)$USER->GetID();
				$userId = $loggedInUserId;
			}
			else
			{
				$loggedInUserId = false;
			}
		}

		if ($userId > 0)
		{
			if (!isset($users[$userId]))
			{
				if ($loggedInUserId === null)
				{
					if (is_object($USER) && method_exists($USER, 'GetID'))
					{
						$loggedInUserId = (int)$USER->GetID();
					}
				}

				if ((int)$userId === $loggedInUserId)
				{
					$users[$userId] = (bool)$USER->isAdmin();
				}
				else
				{

					$ar = \CUser::GetUserGroup($userId);
					if (in_array(1, $ar, true) || in_array('1', $ar, true))
						$users[$userId] = true;    // user is admin
					else
						$users[$userId] = false;    // user isn't admin
				}
			}

			$isAdmin = $users[$userId];
		}

		return ($isAdmin);
	}

	public static function isExternalUser($userID): bool
	{
		$userID = (int)$userID;
		if ($userID <= 0)
		{
			return false;
		}

		static $result = [];

		if (array_key_exists($userID, $result))
		{
			return $result[$userID];
		}

		if (!ModuleManager::isModuleInstalled('extranet'))
		{
			$result[$userID] = false;

			return $result[$userID];
		}

		$dbResult = \CUser::getList(
			'ID',
			'ASC',
			['ID_EQUAL_EXACT' => $userID],
			['FIELDS' => ['ID'], 'SELECT' => ['UF_DEPARTMENT']]
		);

		$user = $dbResult->Fetch();

		$result[$userID] = !(isset($user['UF_DEPARTMENT'][0]) && is_array($user) && $user['UF_DEPARTMENT'][0] > 0);

		return $result[$userID];
	}

	public static function isCollaber(int $userId): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			return false;
		}

		if (
			!class_exists('\Bitrix\Extranet\Service\ServiceContainer')
			|| !method_exists('\Bitrix\Extranet\Service\ServiceContainer', 'getInstance')
			|| !method_exists(\Bitrix\Extranet\Service\ServiceContainer::getInstance(), 'getCollaberService')
		)
		{
			return false;
		}

		$collaberService = \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService();

		return $collaberService->isCollaberById($userId);
	}

	public static function formatName($data, $siteId = false, $format = null)
	{
		if ($format === null)
		{
			$format = static::getUserNameFormat($siteId);
		}

		return \CUser::formatName($format, $data, true, false);
	}

	public static function getUserNameFormat($siteId = '')
	{
		return str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), (string)\CSite::GetNameFormat(false, $siteId));
	}
}