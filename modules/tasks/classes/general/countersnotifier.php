<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @deprecated
 */

IncludeModuleLangFile(__FILE__);

class CTaskCountersNotifier
{
	public static function onAfterTimeManagerDayStart($arData)
	{
		if ( ! (
			isset($arData['USER_ID'], $arData['MODIFIED_BY'])
			&& ($arData['USER_ID'] > 0)
			&& ($arData['MODIFIED_BY'] > 0)
			&& ($arData['MODIFIED_BY'] == $arData['USER_ID'])
			&& IsModuleInstalled("im")
			&& CModule::IncludeModule("im")
		))
		{
			return;
		}

		$recipientId = (int) $arData['USER_ID'];

		try
		{
			$tasksCounter = CTaskListCtrl::getMainCounterForUser($recipientId);
			if ($tasksCounter <= 0)
				return;

			/** @noinspection PhpDeprecationInspection */
			CIMNotify::Add(array(
				'FROM_USER_ID' => 0,
				'TO_USER_ID' => $recipientId,
				'NOTIFY_MODULE' => 'tasks',
				'NOTIFY_EVENT' => 'notice',
				'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
				'NOTIFY_TAG' => 'TASKS|COUNTERS_NOTICE|' . $recipientId,
				//'NOTIFY_SUB_TAG' => 'TASKS|COUNTERS_NOTICE|' . $recipientId,
				'MESSAGE' => CTasksTools::getMessagePlural(
					$tasksCounter,
					'TASKS_COUNTERS_NOTICE_CONTENT_V2',
					array(
						'#TASKS_COUNT#' => $tasksCounter,
						'#HREF#'        => self::getTasksListLink($recipientId)
					)
				),
				'TITLE' => GetMessage('TASKS_COUNTERS_NOTICE_TITLE')
			));
		}
		catch (Exception $e)
		{
			CTaskAssert::logWarning(
				'[0xb83d6845] unexpected exception in CTaskCountersNotifier::onAfterTimeManagerDayStart()'
				. ', file: ' . $e->getFile() . ', line: ' . $e->getLine() 
				. ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
			);
		}
	}


	public static function getTasksListLink($userId)
	{
		return (tasksServerName() . str_replace(
			array('#user_id#', '#USER_ID#'),
			array((int)$userId, (int)$userId),
			COption::GetOptionString(
				'tasks',
				'paths_task_user',
				'/company/personal/user/#user_id#/tasks/',	// by default
				SITE_ID
			)
		));
	}
}
