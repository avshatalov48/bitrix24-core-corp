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
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.task.list");

class TasksTaskCalendarComponent extends TasksTaskListComponent implements  \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $errorCollection;

	public function configureActions()
	{
		return [];
	}

	protected function doPreAction()
	{
		//$this->grid = Grid::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);
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
		return 500;
	}

	protected function getData()
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest()->toArray();
		if (isset($request['task_calendar_action']) && $request['task_calendar_action'] === 'LOAD_ENTRIES')
		{
			$parameters = ['ERRORS' => $this->errors];
			$parameters['MAKE_ACCESS_FILTER'] = true;
			$this->listParameters['filter']['ONLY_ROOT_TASKS'] = 'N';

			$getListParameters = [
				'order' => $this->getOrder(),
				'select' => $this->getSelect(),
				'legacyFilter' => $this->listParameters['filter'],
			];

			if (!isset($this->listParameters['filter']['::SUBFILTER-DEADLINE'])
				&& \Bitrix\Main\Type\Date::isCorrect($request['deadlineFrom'])
				&& \Bitrix\Main\Type\Date::isCorrect($request['deadlineTo'])
			)
			{
				$this->listParameters['filter']['::SUBFILTER-DEADLINE'] = [
					'>=DEADLINE' => $request['deadlineFrom'],
					'<=DEADLINE' => $request['deadlineTo']
				];
			}

			if ($this->exportAs === false)
			{
				$getListParameters['NAV_PARAMS'] = [
					// 'nPageSize' => $this->getPageSize(),
					'bDescPageNumbering' => false,
					'NavShowAll' => true,
					'bShowAll' => true,
					'showAlways' => false,
					'SHOW_ALWAYS' => false,
					'iNumPage' => 1
				];
			}

			if (array_key_exists('USER_ID', $this->arParams))
			{
				$parameters['TARGET_USER_ID'] = $this->arParams['USER_ID'];
			}

			$mgrResult = Manager\Task::getList($this->userId, $getListParameters, $parameters);

			if (array_key_exists('TAG', array_flip($getListParameters['select'])))
			{
				$mgrResult['DATA'] = $this->mergeWithTags($mgrResult['DATA']);
			}

			$this->arResult['LIST'] = $mgrResult['DATA'];

			if ($this->errors->checkHasFatals())
			{
				return;
			}

			$taskIds = [];
			$this->arResult['TASKS_LINKS'] = [];

			foreach($this->arResult['LIST'] as $item)
			{
				$taskId = $item['ID'];

				$taskParameters = ParameterTable::getList(['filter' => ['TASK_ID' => $taskId]])->fetchAll();
				$this->arResult['LIST'][$taskId]['SE_PARAMETER'] = $taskParameters;

				$taskIds[] = $taskId;
			}

			$res = ProjectDependenceTable::getListByLegacyTaskFilter($this->listParameters['filter']);
			while($item = $res->fetch())
			{
				if(in_array($item['TASK_ID'], $taskIds))
				{
					$this->arResult['TASKS_LINKS'][$item['TASK_ID']][] = $item;
				}
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

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function checkParameters()
	{
		parent::checkParameters();
		$this->arParams['LAZY_LOAD'] = false;
	}
}