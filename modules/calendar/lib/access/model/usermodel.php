<?php

namespace Bitrix\Calendar\Access\Model;

use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Main\Engine\CurrentUser;

class UserModel extends \Bitrix\Main\Access\User\UserModel
{

	public function getRoles(): array
	{
		//stub
		return [];
	}

	public function getPermission(string $permissionId): ?int
	{
		//stub
		return 0;
	}

	public function isSocNetAdmin(string $xmlId): bool
	{
		if (
			(
				$xmlId ===  Dictionary::CALENDAR_TYPE['group']
				|| $xmlId === Dictionary::CALENDAR_TYPE['user']
				|| \CCalendar::IsBitrix24()
			)
			&& \CCalendar::IsSocNet()
			&& \CCalendar::IsSocnetAdmin()
		)
		{
			return true;
		}

		return false;
	}
}