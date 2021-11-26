<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
/** This is alfa version of component! Don't use it! */
/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */


use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\Grid;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Util\Type\DateTime;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.task.list");

class TasksTaskGanttComponent extends TasksTaskListComponent
{
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

		foreach ($this->arResult['LIST'] as $k => $item)
		{
			$taskId = $item['ID'];

			$taskParameters = ParameterTable::getList(['filter' => ['TASK_ID' => $taskId]])->fetchAll();
			$this->arResult['LIST'][$taskId]['SE_PARAMETER'] = $taskParameters;

			$taskIds[] = $taskId;

			$this->arResult['GROUP_IDS'][] = $item['GROUP_ID'];
			$this->arResult['LIST'][$k] = $this->prepareRow($item);
		}

		$res = ProjectDependenceTable::getListByLegacyTaskFilter($this->listParameters['filter']);
		while ($item = $res->fetch())
		{
			if (in_array($item['TASK_ID'], $taskIds))
			{
				$this->arResult['TASKS_LINKS'][$item['TASK_ID']][] = $item;
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
		$item['GROUP_NAME'] = htmlspecialcharsbx($item['GROUP_NAME']);

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