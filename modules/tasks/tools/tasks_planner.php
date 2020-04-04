<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");


if (check_bitrix_sessid() && CModule::IncludeModule('tasks'))
{
	CUtil::JSPostUnescape();

	$action = $_REQUEST['action'];
	$site_id = $_REQUEST['site_id'];

	if($action == 'list')
	{
		$APPLICATION->ShowAjaxHead();

		/*
		related to http://jabber.bx/view.php?id=19527
		// TODO: needs good synchronization first
		$info = CTimeMan::GetRuntimeInfo(true);
		$arTasksIds = array();
		if (is_array($info) && isset($info['TASKS']))
		{
			foreach ($info['TASKS'] as $arTask)
				$arTasksIds[] = (int) $arTask['ID'];
		}
		*/

		$APPLICATION->IncludeComponent(
			"bitrix:tasks.task.selector",
			".default",
			array(
				// TODO: needs good synchronization first "MULTIPLE" => "Y",
				"MULTIPLE" => "N",
				"NAME" => "PLANNER_TASKS",
				// TODO: needs good synchronization first "VALUE" => $arTasksIds,
				"VALUE" => '',
				"POPUP" => "N",
				"ON_SELECT" => "PLANNER_ADD_TASK_" . $_REQUEST['suffix'],
				"PATH_TO_TASKS_TASK" => str_replace('#USER_ID#', $USER->GetID(), COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', $site_id)),
				"SITE_ID" => $site_id,
				"FILTER" => array(
					'DOER' => $USER->GetID(),
					'STATUS' => array(
						-2,
						-1,
						CTasks::STATE_NEW,
						CTasks::STATE_PENDING,
						CTasks::STATE_IN_PROGRESS,
						CTasks::STATE_DEFERRED
					)
				),
				"SELECT" => array('ID', 'TITLE', 'STATUS'),
				'HIDE_ADD_REMOVE_CONTROLS' => 'Y'
			),
			null,
			array("HIDE_ICONS" => "Y")
		);
	}
}
