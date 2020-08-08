<?php
namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Manager
{
	const TASKS_RECYCLEBIN_ENTITY = 'tasks_task';
	const TASKS_TEMPLATE_RECYCLEBIN_ENTITY = 'tasks_template';
	const MODULE_ID = 'tasks';

	/**
	 * @return EventResult
	 */
	public static function OnModuleSurvey(): EventResult
	{
		$data = [];

		$data[self::TASKS_RECYCLEBIN_ENTITY] = [
			'NAME'    => Loc::getMessage('TASKS_RECYCLEBIN_ENTITY_NAME'),
			'HANDLER' => Task::class,
		];
		$data[self::TASKS_TEMPLATE_RECYCLEBIN_ENTITY] = [
			'NAME'    => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_ENTITY_NAME'),
			'HANDLER' => Template::class,
		];

		return new EventResult(
			EventResult::SUCCESS,
			['NAME' => Loc::getMessage('TASKS_RECYCLEBIN_MODULE_NAME'), 'LIST' => $data],
			self::MODULE_ID
		);
	}

	/**
	 * @return EventResult
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAdditionalDataRequest(): EventResult
	{
		$additionalData = [
			self::TASKS_RECYCLEBIN_ENTITY => Task::getAdditionalData(),
			self::TASKS_TEMPLATE_RECYCLEBIN_ENTITY => Template::getAdditionalData(),
		];

		return new EventResult(
			EventResult::SUCCESS,
			['NAME' => Loc::getMessage('TASKS_RECYCLEBIN_MODULE_NAME'), 'ADDITIONAL_DATA' => $additionalData],
			self::MODULE_ID
		);
	}
}