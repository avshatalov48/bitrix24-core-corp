<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet\UStat;

class TasksEventHandler
{
	const SECTION = 'TASKS';

	const TITLE = 'INTRANET_USTAT_SECTION_TASKS_NAME';

	public static function getTitle()
	{
		IncludeModuleLangFile(__FILE__);

		return GetMessage(static::TITLE);
	}

	public static function registerListeners()
	{
		RegisterModuleDependences("tasks", "OnTaskAdd", "intranet", "\\".__CLASS__, "onTaskAddEvent");
		RegisterModuleDependences("tasks", "OnTaskUpdate", "intranet", "\\".__CLASS__, "onTaskUpdateEvent");
		RegisterModuleDependences("tasks", "OnTaskElapsedTimeAdd", "intranet", "\\".__CLASS__, "onTaskElapsedTimeAddEvent");
		RegisterModuleDependences("tasks", "OnAfterCommentAdd", "intranet", "\\".__CLASS__, "onAfterCommentAddEvent");
	}

	public static function unregisterListeners()
	{
		UnRegisterModuleDependences("tasks", "OnTaskAdd", "intranet", "\\".__CLASS__, "onTaskAddEvent");
		UnRegisterModuleDependences("tasks", "OnTaskUpdate", "intranet", "\\".__CLASS__, "onTaskUpdateEvent");
		UnRegisterModuleDependences("tasks", "OnTaskElapsedTimeAdd", "intranet", "\\".__CLASS__, "onTaskElapsedTimeAddEvent");
		UnRegisterModuleDependences("tasks", "OnAfterCommentAdd", "intranet", "\\".__CLASS__, "onAfterCommentAddEvent");
	}

	public static function onTaskAddEvent($id, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onTaskUpdateEvent($id, $arFields)
	{
		$arTask = $arFields['META:PREV_FIELDS'];

		foreach ($arFields as $fieldName => $fieldValue)
		{
			// skip unexpected fields
			if (!isset($arTask[$fieldName]))
			{
				continue;
			}

			// skip not changed fields
			if ($fieldValue == $arTask[$fieldName])
			{
				continue;
			}

			switch($fieldName)
			{
				case 'STATUS':
					if ($fieldValue == \CTasks::STATE_IN_PROGRESS)
					{
						UStat::incrementCounter(static::SECTION);
					}
					elseif ($fieldValue == \CTasks::STATE_COMPLETED)
					{
						UStat::incrementCounter(static::SECTION);
					}
					break;

				case 'PRIORITY':
					UStat::incrementCounter(static::SECTION);
					break;

				case 'RESPONSIBLE_ID':
					UStat::incrementCounter(static::SECTION);
					break;

				case 'DEADLINE':
					UStat::incrementCounter(static::SECTION);
					break;

				case 'MARK':
					UStat::incrementCounter(static::SECTION);
					break;
			}
		}
	}

	public static function onTaskElapsedTimeAddEvent($id, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}

	public static function onAfterCommentAddEvent($id, $arFields)
	{
		UStat::incrementCounter(static::SECTION);
	}
}