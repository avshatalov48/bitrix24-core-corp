<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class TasksTaskCalendarAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);
		\Bitrix\Main\Loader::includeModule('tasks');
	}

	public function changeDeadlineAction($taskId, $deadline)
	{
		$taskId = intval($taskId);
		$result = false;
		$task = \CTaskItem::getInstance($taskId, $this->getCurrentUser()->getId());
		if ($task->checkAccess(\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_DEADLINE))
		{
			$result = $task->update(['DEADLINE' => $deadline]);
		}
		return $result;
	}
}