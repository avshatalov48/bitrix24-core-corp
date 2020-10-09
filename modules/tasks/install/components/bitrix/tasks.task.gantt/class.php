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

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.task.list");

class TasksTaskGanttComponent extends TasksTaskListComponent
{
	protected function doPreAction()
	{
		$this->grid = Grid::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);
		$this->filter = Filter::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);

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

		// TODO вынести надо бы
		$taskIds = [];
		$this->arResult['TASKS_LINKS'] = [];

		foreach ($this->arResult['LIST'] as $item)
		{
			$taskId = $item['ID'];

			$taskParameters = ParameterTable::getList(['filter' => ['TASK_ID' => $taskId]])->fetchAll();
			$this->arResult['LIST'][$taskId]['SE_PARAMETER'] = $taskParameters;

			$taskIds[] = $taskId;
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

		$arParams =& $this->arParams;
		static::tryParseStringParameter($arParams['PROJECT_VIEW'], 'N');
	}
}