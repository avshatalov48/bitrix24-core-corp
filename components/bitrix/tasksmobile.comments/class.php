<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

class TasksMobileCommentsComponent extends CBitrixComponent
{
	private Collection $errors;

	private function checkModules(): bool
	{
		if (!Loader::includeModule('forum'))
		{
			$this->errors->add('FORUM_MODULE_NOT_INSTALLED', 'Forum module is not installed');
		}

		return $this->errors->checkNoFatals();
	}

	private function checkPermissions(): bool
	{
		$currentUserId = $this->arResult['USER_ID'] = User::getId();
		$targetUserId = (int)$this->arParams['USER_ID'];

		if (!$currentUserId)
		{
			$this->errors->add('ACCESS_DENIED', 'Current user is not defined');
		}

		if ($currentUserId !== $targetUserId)
		{
			$this->errors->add('ACCESS_DENIED', 'Access denied');
		}

		return $this->errors->checkNoFatals();
	}

	private function checkParameters(): void
	{
		$this->arResult['NAME_TEMPLATE'] = (
			empty($this->arParams['NAME_TEMPLATE'])
				? CSite::GetNameFormat(false)
				: str_replace(['#NOBR#','#/NOBR#'], ['', ''], $this->arParams['NAME_TEMPLATE'])
		);
		$this->arResult['DATE_TIME_FORMAT'] = $this->arParams['DATE_TIME_FORMAT'];
		$this->arResult['GUID'] = $this->arParams['GUID'];
		$this->arResult['PATH_TEMPLATE_TO_USER_PROFILE'] = $this->arParams['PATH_TEMPLATE_TO_USER_PROFILE'];
	}

	public function getData(): void
	{
		try
		{
			$task = new CTaskItem($this->arParams['TASK_ID'], $this->arParams['USER_ID']);
			$taskData = $task->getData(
				false,
				[
					'select' => ['ID', 'CREATED_BY', 'STATUS', 'FORUM_ID'],
				]
			);
		}
		catch (TasksException $exception)
		{
			$this->errors->add($exception->getCode(), $exception->getMessageOrigin());
			return;
		}

		$taskId = (int)$taskData['ID'];

		$this->arResult['TASK'] = $taskData;
		$this->arResult['FORUM_ID'] = ($taskData['FORUM_ID'] ?: CTasksTools::getForumIdForIntranet());
		$this->arResult['LOG_ID'] = $this->getLogId($taskId);
		$this->arResult['RESULT_COMMENTS'] = $this->getResultComments($taskId);
	}

	private function getLogId(int $taskId): int
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return 0;
		}

		$logId = 0;

		$res = CSocNetLog::getList(
			[],
			[
				'EVENT_ID' => 'tasks',
				'SOURCE_ID' => $taskId,
			],
			false,
			false,
			['ID']
		);
		if ($item = $res->Fetch())
		{
			$logId = (int)$item['ID'];
		}

		if (!$logId && Loader::includeModule('crm'))
		{
			$res = CCrmActivity::getList(
				[],
				[
					'TYPE_ID' => \CCrmActivityType::Task,
					'ASSOCIATED_ENTITY_ID' => $taskId,
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				false,
				['ID']
			);
			if ($crmActivity = $res->Fetch())
			{
				$res = CSocNetLog::getList(
					[],
					[
						'EVENT_ID' => 'crm_activity_add',
						'ENTITY_ID' => $crmActivity['ID'],
					],
					false,
					false,
					['ID']
				);
				if ($item = $res->Fetch())
				{
					$logId = (int)$item['ID'];
				}
			}
		}

		return $logId;
	}

	private function getResultComments(int $taskId): array
	{
		$resultComments = ResultManager::findResultComments([$taskId])[$taskId];

		return array_fill_keys($resultComments, true);
	}

	public function executeComponent()
	{
		$this->arResult['ERRORS'] = [];

		if (Loader::includeModule('tasks'))
		{
			$this->errors = new Collection();

			if ($this->checkModules() && $this->checkPermissions())
			{
				$this->checkParameters();
				$this->getData();
			}

			$this->arResult['ERRORS'] = $this->errors->getArrayMeta();
		}
		else
		{
			$this->arResult['ERRORS'][] = [
				'CODE' => 'TASKS_MODULE_NOT_INSTALLED',
				'MESSAGE' => 'Tasks module is not installed',
			];
		}

		$this->includeComponentTemplate();
	}
}