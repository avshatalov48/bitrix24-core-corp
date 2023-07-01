<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\ActionException;
use Bitrix\Tasks\Helper\Grid;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Util\Type\DateTime;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.task.list");

class TasksTaskGanttComponent extends TasksTaskListComponent
{
	public function configureActions()
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'addDependence' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'deleteDependence' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'notificationThrottleRelease' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setViewState' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			]
		];
	}

	public function setViewStateAction($state)
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$stateInstance = Filter::getInstance($this->userId)->getListStateInstance();
		if ($stateInstance)
		{
			$stateInstance->setState($state);
			$stateInstance->saveState();
		}

		return [];
	}

	public function notificationThrottleReleaseAction()
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		\CTaskNotifications::throttleRelease();
	}

	/**
	 * @param $taskFrom
	 * @param $taskTo
	 * @param $linkType
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function addDependenceAction($taskFrom, $taskTo, $linkType): array
	{
		if (!Loader::includeModule('tasks'))
		{
			$this->errorCollection->add('MODULE_IS_NOT_INSTALLED', 'The Tasks module is not installed');
			return [];
		}

		$taskFrom = (int)$taskFrom;
		$taskTo = (int)$taskTo;
		if (!$taskFrom || !$taskTo)
		{
			$this->errorCollection->add('BAD_ARGUMENTS', 'Bad arguments detected');
			return [];
		}

		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskFrom)
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskTo)
		)
		{
			$this->addForbiddenError();
			return [];
		}

		try
		{
			$task = new \CTaskItem($taskTo, $this->userId);
			$task->addDependOn($taskFrom, $linkType);
		}
		catch (ActionException $e)
		{
			if (empty($errors = $e->getErrors()))
			{
				$this->errorCollection->add('', $e->getMessageOrigin());
			}
			else
			{
				foreach ($errors as $error)
				{
					if (is_string($error))
					{
						$error = [
							'CODE' => '',
							'MESSAGE' => $error,
						];
					}
					$this->errorCollection->add($error['CODE'], $error['MESSAGE']);
				}
			}

			return [];
		}
		catch (Exception | \CTaskAssertException $e)
		{
			$this->addForbiddenError();
			return [];
		}

		return [];
	}

	/**
	 * @param $taskFrom
	 * @param $taskTo
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function deleteDependenceAction($taskFrom, $taskTo)
	{
		$taskFrom = (int) $taskFrom;
		if (!$taskFrom)
		{
			return null;
		}

		$taskTo = (int) $taskTo;
		if (!$taskTo)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskFrom)
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskTo)
		)
		{
			$this->addForbiddenError();
			return [];
		}

		try
		{
			$task = new \CTaskItem($taskTo, $this->userId);
			$task->deleteDependOn($taskFrom);
		}
		catch(Exception | \CTaskAssertException $e)
		{
			$this->addForbiddenError();
			return [];
		}

		return [];
	}

	protected function loadGrid()
	{
		$userId = (int) $this->arParams["USER_ID"];
		$groupId = (int) $this->arParams["GROUP_ID"];
		$gridId = $this->getGridId($groupId);

		$this->grid = Grid::getInstance($userId, $groupId, $gridId);
		$this->filter = Filter::getInstance($userId, $groupId, $gridId);
	}

	protected function doPreAction()
	{
		$this->loadGrid();

		static::tryParseStringParameter(
			$this->arParams['NEED_GROUP_BY_GROUPS'],
			$this->needGroupByGroups() ? 'Y' : 'N'
		);
		static::tryParseStringParameter(
			$this->arParams['NEED_GROUP_BY_SUBTASKS'],
			$this->needGroupBySubTasks() ? 'Y' : 'N'
		);

		$this->arParams['DEFAULT_ROLEID'] = $this->filter->getDefaultRoleId();
		parent::doPreAction();

		return true;
	}

	protected function getPageSize()
	{
		return 50;
	}

	protected function getData()
	{
		parent::getData();

		$taskIds = [];
		$this->arResult['TASKS_LINKS'] = [];

		foreach ($this->arResult['LIST'] as $key => $task)
		{
			$taskId = (int)$task['ID'];
			$taskIds[] = $taskId;

			$taskParameters = ParameterTable::getList(['filter' => ['TASK_ID' => $taskId]])->fetchAll();
			$task['SE_PARAMETER'] = $taskParameters;

			$this->arResult['GROUP_IDS'][] = $task['GROUP_ID'];
			$this->arResult['LIST'][$key] = $this->prepareRow($task);
		}

		$res = ProjectDependenceTable::getListByLegacyTaskFilter($this->listParameters['filter']);
		while ($item = $res->fetch())
		{
			$taskId = (int)$item['TASK_ID'];
			if (in_array($taskId, $taskIds, true))
			{
				$this->arResult['TASKS_LINKS'][$taskId][] = $item;
			}
		}
	}

	/**
	 * @param int $groupId
	 * @return string
	 */
	private function getGridId(int $groupId): string
	{
		return \Bitrix\Tasks\Helper\FilterRegistry::getId(\Bitrix\Tasks\Helper\FilterRegistry::FILTER_GANTT, $groupId);
	}

	/**
	 * @param array $item
	 * @return array
	 */
	private function prepareRow(array $item): array
	{
		$item['TITLE'] = $this->prepareTitle($item);
		$item['GROUP_NAME'] = htmlspecialcharsbx($item['GROUP_NAME'] ?? null);

		$item['RESPONSIBLE_NAME'] = htmlspecialcharsbx($item['RESPONSIBLE_NAME']);
		$item['RESPONSIBLE_LAST_NAME'] = htmlspecialcharsbx($item['RESPONSIBLE_LAST_NAME']);
		$item['RESPONSIBLE_SECOND_NAME'] = htmlspecialcharsbx($item['RESPONSIBLE_SECOND_NAME']);
		$item['RESPONSIBLE_LOGIN'] = htmlspecialcharsbx($item['RESPONSIBLE_LOGIN']);

		$item['CREATED_BY_NAME'] = htmlspecialcharsbx($item['CREATED_BY_NAME']);
		$item['CREATED_BY_LAST_NAME'] = htmlspecialcharsbx($item['CREATED_BY_LAST_NAME']);
		$item['CREATED_BY_SECOND_NAME'] = htmlspecialcharsbx($item['CREATED_BY_SECOND_NAME']);
		$item['CREATED_BY_LOGIN'] = htmlspecialcharsbx($item['CREATED_BY_LOGIN']);

		return $item;
	}

	/**
	 * @param array $item
	 * @return string
	 */
	private function prepareTitle(array $row): string
	{
		$counter = '';

		if ($this->arParams['CAN_SEE_COUNTERS'])
		{
			$rowCounter = (new \Bitrix\Tasks\Internals\Counter\Template\TaskCounter((int)$this->arParams["USER_ID"]))->getRowCounter((int)$row['ID']);
			if ($rowCounter['VALUE'])
			{
				$counter = "<div class='ui-counter ui-counter-{$rowCounter['COLOR']}'><div class='ui-counter-inner'>{$rowCounter['VALUE']}</div></div>";
			}
		}

		$title = htmlspecialcharsbx($row['TITLE']);
		$counterContainer = "<span class='task-counter-container'>{$counter}</span>";

		return "<span id='changedDate' style='margin-right: 3px'>{$title}</span>".$counterContainer;
	}

	/**
	 * @param int $userId
	 * @param array $row
	 * @return bool
	 */
	private function isMember(int $userId, array $row): bool
	{
		$members = array_unique(
			array_merge(
				[$row['CREATED_BY'], $row['RESPONSIBLE_ID']],
				(is_array($row['ACCOMPLICES']) ? $row['ACCOMPLICES'] : []),
				(is_array($row['AUDITORS']) ? $row['AUDITORS'] : [])
			)
		);
		$members = array_map('intval', $members);

		return in_array($userId, $members, true);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private function isExpired(int $timestamp): bool
	{
		return $timestamp && ($timestamp <= $this->getNow());
	}

	/**
	 * @return int
	 */
	private function getNow(): int
	{
		return (new DateTime())->getTimestamp() + CTimeZone::GetOffset();
	}

	/**
	 * @param string $date
	 * @return int
	 */
	private function getDateTimestamp($date): int
	{
		$timestamp = MakeTimeStamp($date);

		if ($timestamp === false)
		{
			$timestamp = strtotime($date);
			if ($timestamp !== false)
			{
				$timestamp += CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
			}
		}

		return $timestamp;
	}

	protected function getSelect()
	{
		if($this->exportAs != null)
		{
			$columns = array(
				"ID",
				"TITLE",
				"RESPONSIBLE_ID",
				"CREATED_BY",
				"CREATED_DATE",
				"REAL_STATUS",
				"PRIORITY",
				"START_DATE_PLAN",
				"END_DATE_PLAN",
				"DEADLINE",
				"TIME_ESTIMATE",
				"TIME_SPENT_IN_LOGS",
				"CLOSED_DATE",
				"MARK",
				"ADD_IN_REPORT",
				"GROUP_ID"
			);
			return $columns;
		}
		else
		{
			return array('*');
		}
	}

	protected function checkParameters()
	{
		parent::checkParameters();
		static::tryParseStringParameter($this->arParams['PROJECT_VIEW'], 'N');
		$this->arParams['LAZY_LOAD'] = false;
	}
}