<?php
/**
 * Created by PhpStorm.
 * User: maxyc
 * Date: 21.05.18
 * Time: 9:07
 */

namespace Bitrix\Tasks\Integration\Trash;

use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Manager
{
	const TASKS_TRASH_ENTITY = 'tasks_task';
	const TASKS_TEMPLATE_TRASH_ENTITY = 'tasks_template';
	const MODULE_ID = 'tasks';

	public static function OnModuleSurvey()
	{
		$data = [];

		$data[self::TASKS_TRASH_ENTITY] = array(
			'NAME'    => Loc::getMessage('TASKS_TRASH_ENTITY_NAME'),
			'HANDLER' => \Bitrix\Tasks\Integration\Trash\Task::class
		);

		$data[self::TASKS_TEMPLATE_TRASH_ENTITY] = array(
			'NAME'    => Loc::getMessage('TASKS_TEMPLATE_TRASH_ENTITY_NAME'),
			'HANDLER' => \Bitrix\Tasks\Integration\Trash\Template::class
		);

		return new EventResult(
			EventResult::SUCCESS, [
			'NAME' => Loc::getMessage('TASKS_TRASH_MODULE_NAME'),
			'LIST' => $data
		], self::MODULE_ID
		);
	}
}