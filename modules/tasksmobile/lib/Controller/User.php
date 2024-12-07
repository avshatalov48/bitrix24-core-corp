<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Tasks\Ui\Avatar;

class User extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'getCurrentUserData',
			'getCurrentUserDataLegacy',
		];
	}

	public function getCurrentUserDataAction(): array
	{
		return UserRepository::getByIds([$this->getCurrentUser()->getId()]);
	}

	public function getCurrentUserDataLegacyAction(): array
	{
		$currentUserId = (int)$this->getCurrentUser()->getId();

		$userResult = \CUser::GetList(
			'id',
			'asc',
			['ID' => $currentUserId],
			['FIELDS' => ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'PERSONAL_PHOTO', 'WORK_POSITION']]
		);
		if ($user = $userResult->Fetch())
		{
			$userName = \CUser::FormatName(
				\CSite::GetNameFormat(),
				[
					'LOGIN' => $user['LOGIN'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'SECOND_NAME' => $user['SECOND_NAME'],
				],
				true,
				false,
			);

			return [
				'id' => $currentUserId,
				'name' => $userName,
				'icon' => Avatar::getPerson($user['PERSONAL_PHOTO']),
				'workPosition' => $user['WORK_POSITION'],
			];
		}

		return [];
	}
}
