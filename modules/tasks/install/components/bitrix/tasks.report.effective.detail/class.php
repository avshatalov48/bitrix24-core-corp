<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Internals\Counter\EffectiveTable;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");
CBitrixComponent::includeComponentClass("bitrix:tasks.report.effective");

class TasksReportEffectiveDetailComponent extends TasksBaseComponent
{
	protected $pageSizes = array(
		array("NAME" => "5", "VALUE" => "5"),
		array("NAME" => "10", "VALUE" => "10"),
		array("NAME" => "20", "VALUE" => "20"),
		array("NAME" => "50", "VALUE" => "50"),
		array("NAME" => "100", "VALUE" => "100"),
		//Temporary limited by 100
		//array("NAME" => "200", "VALUE" => "200"),
	);

	protected $defaultPageSize = 50;

	protected static function checkPermissions(array &$arParams, array &$arResult,
											   \Bitrix\Tasks\Util\Error\Collection $errors, array $auxParams = array())
	{
		$currentUser = User::getId();
		$viewedUser = $arParams['USER_ID'];

		$isAccessible =
			$currentUser == $viewedUser ||
		  	User::isSuper($currentUser) ||
		  	User::isBossRecursively($currentUser, $viewedUser);

		if (!$isAccessible)
		{
			$errors->add('TASKS_MODULE_ACCESS_DENIED', Loc::getMessage("TASKS_COMMON_ACCESS_DENIED"));
		}

		return $errors->checkNoFatals();
	}

	protected function checkParameters()
	{
		// todo
		$arParams = &$this->arParams;
		static::tryParseStringParameter($arParams['GRID_ID'], \TasksReportEffectiveComponent::getFilterId());
		static::tryParseStringParameter($arParams['GROUP_ID'], $_GET['group_id']);

		static::tryParseStringParameter($arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		static::tryParseStringParameter(
			$arParams['PATH_TO_TASK_DETAIL'],
			'/company/personal/user/#user_id#/tasks/task/view/#task_id#/'
		);
		static::tryParseStringParameter($arParams['PATH_TO_GROUP_LIST'], '/workgroups/group/#group_id#/');

		static::tryParseStringParameter($arParams['USE_PAGINATION'], true);
		static::tryParseStringParameter($arParams['DEFAULT_PAGE_SIZE'], $this->defaultPageSize);
		static::tryParseArrayParameter($arParams['PAGE_SIZES'], $this->pageSizes);

		static::tryParseIntegerParameter($arParams['USER_ID'], $this->userId);

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$this->arResult['VIOLATION_LIST'] = $this->getViolationList();
		$this->arParams['HEADERS'] = $this->getGridHeaders();
	}

	private function getViolationList()
	{
		$filterData = $this->getFilterData();
		$datesRange = Effective::getDatesRange();

		$dateFrom = $datesRange['FROM'];
		$dateTo = $datesRange['TO'];

		if (array_key_exists('DATETIME_from', $filterData) && $filterData['DATETIME_from'])
		{
			$dateFrom = new DateTime($filterData['DATETIME_from']);
		}
		if (array_key_exists('DATETIME_to', $filterData) && $filterData['DATETIME_to'])
		{
			$dateTo = new DateTime($filterData['DATETIME_to']);
		}

		$violationFilter = [
			'USER_ID' => $this->arParams['USER_ID'],
			'IS_VIOLATION' => 'Y',
			'>TASK.RESPONSIBLE_ID' => 0,
			[
				'LOGIC' => 'OR',
				[
					'>=DATETIME' => $dateFrom,
					'<=DATETIME' => $dateTo,
				],
				[
					'<=DATETIME' => $dateTo,
					'=DATETIME_REPAIR' => false,
				],
				[
					'<=DATETIME' => $dateTo,
					'>=DATETIME_REPAIR' => $dateFrom,
				]
			]
		];

		if ($filterData['GROUP_ID'] > 0)
		{
			$violationFilter['GROUP_ID'] = $filterData['GROUP_ID'];
		}

		$navPageSize = $this->getPageSize();
		$nav = new PageNavigation("nav-effective");
		$nav->allowAllRecords(true)->setPageSize($navPageSize)->initFromUri();

		$violations = EffectiveTable::getList([
			'count_total' => true,
			'filter' => $violationFilter,
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
			'order' => ['DATETIME' => 'DESC', 'TASK_TITLE' => 'ASC'],
			'select' => [
				'TASK_ID',
				'DATE' => 'DATETIME',
				'DATE_REPAIR' => 'DATETIME_REPAIR',
				'TASK_TITLE',
				'TASK_DEADLINE',
				'USER_TYPE',
				'TASK_ORIGINATOR_ID' => 'TASK.CREATOR.ID',
				'TASK_ZOMBIE' => 'TASK.ZOMBIE',
				'GROUP_ID',
				'GROUP_NAME' => 'GROUP.NAME',
			],
			'group' => ['DATE'],
		]);

		$nav->setRecordCount($violations->getCount());
		$this->arResult['NAV'] = $nav;

		$data = $violations->fetchAll();

		return $data;
	}

	/**
	 * @return array
	 */
	protected function getFilterData()
	{
		$filters = \TasksReportEffectiveComponent::getFilterList();
		$filterOptions = $this->getFilterOptions();

		return $filterOptions->getFilter($filters);
	}

	/**
	 * @return Filter\Options
	 */
	private static function getFilterOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = new Filter\Options(
				\TasksReportEffectiveComponent::getFilterId(), \TasksReportEffectiveComponent::getPresetList()
			);
		}

		return $instance;
	}

	protected function getPageSize()
	{
		$navParams = $this->getGridOptions()->getNavParams(
			array(
				'nPageSize' => $this->defaultPageSize
			)
		);

		return (int)$navParams['nPageSize'];
	}

	/**
	 * @return Grid\Options
	 */
	private function getGridOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = (new Grid\Options($this->arParams['GRID_ID']));
		}

		return $instance;
	}

	private function getGridHeaders()
	{
		return array(
			'TASK'        => array(
				'id'       => 'TASK',
				'name'     => GetMessage('TASKS_COLUMN_TASK'),
				'editable' => false,
				'default'  => true
			),
			'DATE'        => array(
				'id'       => 'DATE',
				'name'     => GetMessage('TASKS_COLUMN_CREATED_DATE'),
				'editable' => false,
				'default'  => true
			),
			'DATE_REPAIR' => array(
				'id'       => 'DATE_REPAIR',
				'name'     => GetMessage('TASKS_COLUMN_REPAIR_DATE'),
				'editable' => false,
				'default'  => true
			),
			'USER_TYPE'   => array(
				'id'       => 'USER_TYPE',
				'name'     => GetMessage('TASKS_COLUMN_USER_TYPE2'),
				'editable' => false,
				'default'  => false
			),
			'DEADLINE'    => array(
				'id'       => 'DEADLINE',
				'name'     => GetMessage('TASKS_COLUMN_DEADLINE'),
				'editable' => false,
				'default'  => false
			),
			'ORIGINATOR'  => array(
				'id'       => 'ORIGINATOR',
				'name'     => GetMessage('TASKS_COLUMN_ORIGINATOR'),
				'editable' => false,
				'default'  => false
			),
			'GROUP'       => array(
				'id'       => 'GROUP',
				'name'     => GetMessage('TASKS_COLUMN_GROUP'),
				'editable' => false,
				'default'  => false
			),
		);
	}
}
