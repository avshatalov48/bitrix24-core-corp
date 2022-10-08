<?php

namespace Bitrix\Tasks\Util;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

Loc::loadMessages(__FILE__);

final class Restriction
{
	/**
	 * @param $userId
	 * @return bool
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function checkCanCreateDependence($userId = 0): bool
	{
		// you can not skip this check for admin, because on bitrix24 admin is just one of regular users

		// yes: you are using box, or you are in trial mode
		if (Bitrix24\Task::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASKS_GANTT))
		{
			return true;
		}

		if (TaskLimit::isLimitExceeded())
		{
			return (ProjectDependenceTable::getLinkCountForUser($userId) < 5);
		}

		return true;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public static function canManageTask($userId = 0): bool
	{
		return Bitrix24\Task::checkToolAvailable('tasks');
	}
}