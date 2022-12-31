<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Ui\Avatar;

Loader::requireModule('tasks');

class User extends Controller
{
	public function configureActions(): array
	{
		return [
			'getUsersData' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getUsersDataAction(array $userIds): array
	{
		if (empty($userIds))
		{
			return [];
		}

		$users = [];
		$userResult = \CUser::GetList(
			'id',
			'asc',
			['ID' => implode('|', $userIds)],
			['FIELDS' => ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION']]
		);
		while ($user = $userResult->Fetch())
		{
			$userId = (int)$user['ID'];
			$userName = \CUser::FormatName(
				\CSite::GetNameFormat(),
				[
					'LOGIN' => $user['LOGIN'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'SECOND_NAME' => $user['SECOND_NAME'],
				],
				true,
				false
			);

			$users[$userId] = [
				'id' => $userId,
				'name' => $userName,
				'icon' => Avatar::getPerson($user['PERSONAL_PHOTO']),
				'workPosition' => $user['WORK_POSITION'],
			];
		}

		return $users;
	}
}