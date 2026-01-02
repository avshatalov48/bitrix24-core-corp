<?php

namespace Bitrix\Tasks\Util;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Bitrix24;

Loc::loadMessages(__FILE__);

final class Restriction
{
	/**
	 * @param $userId
	 * @return bool
	 */
	public static function canManageTask($userId = 0): bool
	{
		return Bitrix24\Task::checkToolAvailable('tasks');
	}
}
