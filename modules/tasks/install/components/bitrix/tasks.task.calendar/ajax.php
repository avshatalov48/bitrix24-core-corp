<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Control\Task;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class TasksTaskCalendarAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected Task $handler;

	protected int $userId = 0;

	public function changeDeadlineAction($taskId, $deadline): ?bool
	{
		$taskId = (int)$taskId;
		$deadline = (string)$deadline;
		if ($taskId <= 0 || empty($deadline))
		{
			return false;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, $taskId))
		{
			return false;
		}

		try
		{
			$result = $this->handler->update($taskId, ['DEADLINE' => $deadline]);
		}
		catch (Throwable)
		{
			return false;
		}

		return false !== $result;
	}

	protected function init(): void
	{
		parent::init();
		Loader::includeModule('tasks');

		$this->userId = CurrentUser::get()->getId();
		$this->handler = new Task($this->userId);
	}
}