<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksReportEffectiveComponent extends TasksBaseComponent
{
	protected $userId;
	protected $groupId;

	protected static function checkPermissions(array &$arParams, array &$arResult,
											   \Bitrix\Tasks\Util\Error\Collection $errors, array $auxParams = array())
	{
		$currentUser = User::getId();
		$viewedUser = $arParams['USER_ID'];

		if (!$viewedUser)
		{
			$viewedUser = $_REQUEST['ACTION'][0]['ARGUMENTS']['userId']; //TODO 18.0.0 IN NEW REST
		}

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

	public static function getAllowedMethods()
	{
		return array(
			'getEfficiencyData'
		);
	}

	protected function checkParameters()
	{
		// todo
		$arParams = &$this->arParams;
		static::tryParseStringParameter($arParams['FILTER_ID'], 'GRID_EFFECTIVE');
		static::tryParseStringParameter($arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		static::tryParseStringParameter($arParams['PATH_TO_EFFECTIVE_DETAIL'], '/company/personal/user/#user_id#/tasks/effective/show/');
		static::tryParseStringParameter($arParams['PATH_TO_TASK_ADD'], '/company/personal/user/'.User::getId().'/tasks/task/edit/0/');
		static::tryParseStringParameter($arParams['USE_PAGINATION'], true);
		static::tryParseStringParameter($arParams['DEFAULT_PAGE_SIZE'], $this->defaultPageSize);
		static::tryParseArrayParameter($arParams['PAGE_SIZES'], $this->pageSizes);

		$this->userId = $this->arParams['USER_ID'] ? $this->arParams['USER_ID'] : User::getId();
		$this->groupId = $this->arParams['GROUP_ID'] ? $this->arParams['GROUP_ID'] : 0;

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$this->arResult['FILTERS'] = static::getFilterList();
		$this->arResult['PRESETS'] = static::getPresetList();

		$this->arResult['EFFECTIVE_DATE_START'] = $this->getEffectiveDate();

		$this->arResult['JS_DATA']['userId'] = $this->arParams['USER_ID'];
		$this->arResult['JS_DATA']['efficiencyData'] = static::getEfficiencyData($this->arParams['USER_ID']);
	}

	private function getEffectiveDate()
	{
		$defaultDate = new Datetime();
		$format='Y-m-d H:i:s';
		$dateFromDb = Config\Option::get('tasks', 'effective_date_start', $defaultDate->format($format));
		$date = new DateTime($dateFromDb, $format);

		$dateFormatted = GetMessage('TASKS_EFFECTIVE_DATE_FORMAT', array(
			'#DAY#'          =>$date->format('d'),
			'#MONTH_NAME#'   => GetMessage('TASKS_MONTH_'.(int)$date->format('m')),
			'#YEAR_IF_DIFF#' =>$date->format('Y') != date('Y') ? $date->format('Y') : ''
		));

		return $dateFormatted;
	}

	public static function getFilterList()
	{
		return array(
			'GROUP_ID' => array(
				'id' => 'GROUP_ID',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_GROUP_ID'),
				//				'params' => array('multiple' => 'Y'),
				'type' => 'custom_entity',
				'default' => true,
				'selector' => array(
					'TYPE' => 'group',
					'DATA' => array(
						'ID' => 'group',
						'FIELD_ID' => 'GROUP_ID'
					)
				)
			),
			'DATETIME' => array(
				'id' => 'DATETIME',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_DATE'),
				'type' => 'date',
				"exclude" => array(
					Filter\DateType::NONE,
					Filter\DateType::TOMORROW,
					Filter\DateType::PREV_DAYS,
					Filter\DateType::NEXT_DAYS,
					Filter\DateType::NEXT_WEEK,
					Filter\DateType::NEXT_MONTH
				),
				'default' => true,
			),
		);
	}

	public static function getPresetList()
	{
		return Effective::getPresetList();
	}

	public static function getEfficiencyData($userId)
	{
		$filter = static::processFilter();
		$datesRange = Effective::getDatesRange();

		$groupId = 0;
		$groupByHour = false;
		$dateFrom = $datesRange['FROM'];
		$dateTo = $datesRange['TO'];

		if (array_key_exists('>=DATETIME', $filter))
		{
			$dateFrom = new DateTime($filter['>=DATETIME']);
		}
		if (array_key_exists('<=DATETIME', $filter))
		{
			$dateTo = new DateTime($filter['<=DATETIME']);
		}
		if (array_key_exists('GROUP_ID', $filter))
		{
			$groupId = $filter['GROUP_ID'];
		}
		if (isset($filter['::']) && $filter['::'] == 'BY_DAY')
		{
			unset($filter['::']);
			$groupByHour = true;
		}

		$tasksCounters = Effective::getCountersByRange($dateFrom, $dateTo, $userId, $groupId);

		$efficiency = 100;
		$violations = $tasksCounters['VIOLATIONS'];
		$inProgress = $tasksCounters['IN_PROGRESS'];

		if ($inProgress > 0)
		{
			$efficiency = (int)round(100 - ($violations / $inProgress) * 100);
		}
		else if ($violations > 0)
		{
			$efficiency = 0;
		}

		if ($efficiency < 0)
		{
			$efficiency = 0;
		}

		$graphData = static::getGraphData($dateFrom, $dateTo, $userId, $groupId, $groupByHour);

		return array(
			'EFFICIENCY' => $efficiency,
			'COMPLETED' => $tasksCounters['COMPLETED'],
			'VIOLATIONS' => $tasksCounters['VIOLATIONS'],
			'IN_PROGRESS' => $tasksCounters['IN_PROGRESS'],
			'GRAPH_DATA' => $graphData,
			'GRAPH_MIN_PERIOD' => ($groupByHour? 'hh' : 'DD')
		);
	}

	private static function getGraphData($dateFrom, $dateTo, $userId, $groupId, $groupByHour)
	{
		$graphData = [];
		$graphDataRes = Effective::getEfficiencyForGraph($dateFrom, $dateTo, $userId, $groupId, ($groupByHour? 'HOUR' : ''));

		foreach ($graphDataRes as $row)
		{
			if ($groupByHour)
			{
				$row['DATE'] = $row['HOUR'];
			}
			else
			{
				$row['DATE'] = $row['DATE']->format('Y-m-d');
			}

			$row['EFFECTIVE'] = round($row['EFFECTIVE']);

			$graphData[] = $row;
		}

		return $graphData;
	}

	private static function processFilter()
	{
		static $filter = [];

		if (!$filter)
		{
			$filterList = static::getFilterList();
			$rawFilter = static::getFilterOptions()->getFilter($filterList);

			if (!array_key_exists('FILTER_APPLIED', $rawFilter) || $rawFilter['FILTER_APPLIED'] != true)
			{
				return [];
			}

			foreach ($filterList as $item)
			{
				switch ($item['type'])
				{
					case 'custom_entity':
						if ($rawFilter[$item['id']])
						{
							$filter[$item['id']] = $rawFilter[$item['id']];
						}
						break;

					case 'date':
						if (array_key_exists($item['id'] . '_from', $rawFilter) && !empty($rawFilter[$item['id'] . '_from']))
						{
							$filter['>=' . $item['id']] = $rawFilter[$item['id'] . '_from'];
						}
						if (array_key_exists($item['id'] . '_to', $rawFilter) && !empty($rawFilter[$item['id'] . '_to']))
						{
							$filter['<=' . $item['id']] = $rawFilter[$item['id'] . '_to'];
						}

						if (static::checkByDayFiltering($rawFilter, $item, $filter))
						{
							$filter['::'] = 'BY_DAY';
						}
						break;
				}
			}
		}

		return $filter;
	}

	private static function checkByDayFiltering($rawFilter, $item, $filter)
	{
		$dateTypesForDayFiltering = [
			Filter\DateType::YESTERDAY,
			Filter\DateType::CURRENT_DAY,
			Filter\DateType::EXACT
		];
		$rangeType = Filter\DateType::RANGE;

		$dateSel = $rawFilter[$item['id'] . '_datesel'];
		$dateFrom = new DateTime($filter['>=' . $item['id']]);
		$dateTo = new DateTime($filter['<=' . $item['id']]);

		if (
			in_array($dateSel, $dateTypesForDayFiltering) ||
			($dateSel == $rangeType && $dateFrom->format('Y-m-d') == $dateTo->format('Y-m-d'))
		)
		{
			return true;
		}

		return false;
	}
	
	public static function getFilterId()
	{
		return Effective::getFilterId();
	}

	private static function getFilterOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = new Filter\Options(static::getFilterId(), static::getPresetList());
		}

		return $instance;
	}
}
