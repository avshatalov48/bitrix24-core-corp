<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Util\User as TasksUserUtil;

class UserService
{
	private static $usersInfo = [];

	public function getInfoAboutUsers(array $userIds): array
	{
		$users = [];

		foreach ($userIds as $key => $userId)
		{
			$usersInfo = $this->getFromCache($userId);
			if ($usersInfo)
			{
				$users[$userId] = $usersInfo;
				unset($userIds[$key]);
			}
		}

		if (!$userIds)
		{
			return (count($users) === 1 ? current($users) : $users);
		}

		$select = [
			'ID',
			'PERSONAL_PHOTO',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'EXTERNAL_AUTH_ID',
			'UF_DEPARTMENT'
		];

		$queryObject = UserTable::getList([
			'select' => $select,
			'filter' => [
				'ID' => $userIds
			]
		]);
		while ($row = $queryObject->fetch())
		{
			if ($row['PERSONAL_PHOTO'])
			{
				$row['PERSONAL_PHOTO'] = \CFile::resizeImageGet(
					$row['PERSONAL_PHOTO'],
					['width' => 100, 'height' => 100],
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
			}

			$row['USER_NAME'] = TasksUserUtil::formatName($row);

			$pathToUser = str_replace(
				['#user_id#'],
				$row['ID'],
				Option::get('main', 'TOOLTIP_PATH_TO_USER', false, SITE_ID)
			);

			self::$usersInfo[$row['ID']] = $users[$row['ID']] = [
				'id' => $row['ID'],
				'photo' => $row['PERSONAL_PHOTO'],
				'name' => $row['USER_NAME'],
				'pathToUser' => $pathToUser
			];
		}

		return (count($users) === 1 ? current($users) : $users);
	}

	private function getFromCache(int $userId): array
	{
		if (array_key_exists($userId, self::$usersInfo))
		{
			return self::$usersInfo[$userId];
		}
		return [];
	}
}