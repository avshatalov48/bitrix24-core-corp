<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Mobile\Provider\UserRepository;

class User extends Controller
{
	public function configureActions(): array
	{
		return [
			'getByIds' => [
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
		];
	}

	public function getByIdsAction(array $userIds): array
	{
		return UserRepository::getByIds($userIds);
	}
}
