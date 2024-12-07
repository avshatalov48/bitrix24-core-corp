<?php

namespace Bitrix\Tasks\Flow\Controllers\Trait;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Ui\Avatar;
use Bitrix\Tasks\Util\User;

trait UserTrait
{
	private function getUsers(int ...$userIds): array
	{
		$users = User::getData($userIds);

		$response = [];
		foreach ($users as $user)
		{
			$response[$user['ID']] = [
				'ID' => $user['ID'],
				'NAME' => User::formatName($user),
				'AVATAR' => Avatar::getSrc($user['PERSONAL_PHOTO'], 100, 100),
				'WORK_POSITION' => $user['WORK_POSITION'],
				'PATH_TO_PROFILE' => $this->getPathToProfile($user['ID']),
			];
		}

		return $response;
	}

	private function getPathToProfile(int $userId): string
	{
		return str_replace(
			['#user_id#'],
			$userId,
			Option::get('main', 'TOOLTIP_PATH_TO_USER', false, SITE_ID)
		);
	}
}
