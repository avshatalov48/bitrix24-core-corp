<?php
namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Manager
{
	const TASKS_RECYCLEBIN_ENTITY = 'tasks_task';
	const TASKS_TEMPLATE_RECYCLEBIN_ENTITY = 'tasks_template';
	const MODULE_ID = 'tasks';

	public static function OnModuleSurvey()
	{
		$data = [];

		$data[self::TASKS_RECYCLEBIN_ENTITY] = array(
			'NAME'    => Loc::getMessage('TASKS_RECYCLEBIN_ENTITY_NAME'),
			'HANDLER' => \Bitrix\Tasks\Integration\Recyclebin\Task::class
		);

		$data[self::TASKS_TEMPLATE_RECYCLEBIN_ENTITY] = array(
			'NAME'    => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_ENTITY_NAME'),
			'HANDLER' => \Bitrix\Tasks\Integration\Recyclebin\Template::class
		);

		return new EventResult(
			EventResult::SUCCESS, [
			'NAME' => Loc::getMessage('TASKS_RECYCLEBIN_MODULE_NAME'),
			'LIST' => $data
		], self::MODULE_ID
		);
	}
}