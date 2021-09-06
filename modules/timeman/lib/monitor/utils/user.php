<?php
namespace Bitrix\Timeman\Monitor\Utils;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CIntranetUtils;

class User
{
	protected const DEFAULT_AVATAR_WIDTH = 42;
	protected const DEFAULT_AVATAR_HEIGHT = 42;

	protected static $userFields = [];
	protected static $requiredUserFieldsList = ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME','PERSONAL_PHOTO'];

	public static function getCurrentUserId(): ?int
	{
		global $USER;

		return $USER->GetID();
	}

	public static function getCurrentUserName()
	{
		global $USER;

		return $USER->GetFormattedName();
	}

	public static function getCurrentUserInfo(): ?array
	{
		return self::getInfo(self::getCurrentUserId());
	}

	public static function getInfo(int $userId): ?array
	{
		self::preloadUserInfo([$userId]);

		return self::getUserInfo($userId);
	}

	public static function getSubordinateEmployees(int $userId): array
	{
		$subordinateIds = [];

		$users = CIntranetUtils::GetSubordinateEmployees($userId, true, 'Y', array('ID'));
		while ($user = $users->Fetch())
		{
			$subordinateIds[] = (int)$user['ID'];
		}

		return $subordinateIds;
	}

	/**
	 * Gets users fields by Ids
	 *
	 * @param array $userIds
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function preloadUserInfo(array $userIds): void
	{
		$missingUserIds = array_diff($userIds, array_keys(static::$userFields));
		if (count($missingUserIds) === 0)
		{
			return;
		}

		$cursor = UserTable::getList([
			'select' => static::$requiredUserFieldsList,
			'filter' => [
				'=ID' => $missingUserIds
			]
		]);

		foreach ($cursor->getIterator() as $row)
		{
			static::$userFields[$row['ID']] = $row;
		}
	}

	/**
	 * Returns [id, name, link, icon] for the specified use id.
	 *
	 * @param int $userId Id of the user.
	 * @param array $params Additional optional parameters
	 *   <li> avatarWidth int
	 *   <li> avatarHeight int
	 * @return array|null
	 */
	public static function getUserInfo(int $userId, array $params = []): ?array
	{
		static $users = [];

		$userId = (int)$userId;

		if (!$userId)
		{
			return ['name' => Loc::getMessage('TIMEMAN_MONITOR_UTILS_DEFAULT_USER_NAME')];
		}

		if(isset($users[$userId]))
		{
			return $users[$userId];
		}

		// prepare link to profile
		$replaceList = ['user_id' => $userId];
		$template = '/company/personal/user/#user_id#/';
		$link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

		self::preloadUserInfo([$userId]);
		$userFields = static::$userFields[$userId];

		if (!$userFields)
		{
			return ['name' => Loc::getMessage('TIMEMAN_MONITOR_UTILS_DEFAULT_USER_NAME')];
		}

		// format name
		$userName = \CUser::FormatName(
			\CSite::GetNameFormat(),
			[
				'LOGIN' => $userFields['LOGIN'],
				'NAME' => $userFields['NAME'],
				'LAST_NAME' => $userFields['LAST_NAME'],
				'SECOND_NAME' => $userFields['SECOND_NAME']
			],
			true,
			false
		);

		$userName =  !empty($userName) ? $userName : Loc::getMessage('TIMEMAN_MONITOR_UTILS_DEFAULT_USER_NAME');

		// prepare icon
		$fileTmp = \CFile::ResizeImageGet(
			$userFields['PERSONAL_PHOTO'],
			[
				'width' => $params['avatarWidth'] ?? static::DEFAULT_AVATAR_WIDTH,
				'height' => $params['avatarHeight'] ?? static::DEFAULT_AVATAR_HEIGHT
			],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);
		$userIcon = $fileTmp['src'];

		$users[$userId] = [
			'id' => $userId,
			'name' => $userName,
			'link' => $link,
			'icon' => $userIcon
		];

		return $users[$userId];
	}
}

