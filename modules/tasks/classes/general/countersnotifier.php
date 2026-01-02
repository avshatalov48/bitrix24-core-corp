<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 */

use Bitrix\Tasks\V2\Internal\DI\Container;

IncludeModuleLangFile(__FILE__);

class CTaskCountersNotifier
{
	public static function getTasksListLink($userId): string
	{
		$url = Container::getInstance()->getUrlService()->getHostUrl();

		return ($url . str_replace(
				['#user_id#', '#USER_ID#'],
				[(int)$userId, (int)$userId],
				COption::GetOptionString(
					'tasks',
					'paths_task_user',
					'/company/personal/user/#user_id#/tasks/',    // by default
					SITE_ID
				)
			));
	}
}
