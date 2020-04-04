<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Integration\Bitrix24;

Loc::loadMessages(__FILE__);

final class Restriction
{
	public static function checkCanCreateDependence($userId = 0)
	{
		// you can not skip this check for admin, because on bitrix24 admin is just one of regular users

		if(Bitrix24\Task::checkFeatureEnabled('gant'))
		{
			return true; // yes: you are using box, or you are in trial mode
		}

		// generally no, but you can make 5 dependences
		return ProjectDependenceTable::getLinkCountForUser($userId) < 5;
	}

	public static function canManageTask($userId = 0)
	{
		return Bitrix24\Task::checkToolAvailable('tasks');
	}
}