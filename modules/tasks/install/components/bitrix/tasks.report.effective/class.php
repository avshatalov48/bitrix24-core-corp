<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\UI\Filter;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Internals\Effective;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksReportEffectiveComponent
 */
class TasksReportEffectiveComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $userId;
	protected $groupId;

	protected $errorCollection;

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'getEfficiencyData' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function getEfficiencyDataAction($userId)
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$userId = (int) $userId;

		$isAccessible =
			$this->userId === $userId
			|| User::isSuper($this->userId)
			|| User::isBossRecursively($this->userId, $userId);

		if (!$isAccessible)
		{
			$this->addForbiddenError();
			return null;
		}

		return $this->getEfficiencyData($userId);
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'));
	}

	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		$currentUser = User::getId();
		$viewedUser = $arParams['USER_ID'];

		if (!$viewedUser)
		{
			$viewedUser = $_REQUEST['ACTION'][0]['ARGUMENTS']['userId']; //TODO 18.0.0 IN NEW REST
		}

		$isAccessible = $currentUser === (int)$viewedUser
			|| User::isSuper($currentUser)
			|| User::isBossRecursively($currentUser, $viewedUser);

		if (!$isAccessible)
		{
			$errors->add('TASKS_MODULE_ACCESS_DENIED', Loc::getMessage("TASKS_COMMON_ACCESS_DENIED"));
		}

		return $errors->checkNoFatals();
	}

	protected static function checkIfToolAvailable(array &$arParams, array &$arResult, Collection $errors, array $auxParams): void
	{
		parent::checkIfToolAvailable($arParams, $arResult, $errors, $auxParams);

		if (!$arResult['IS_TOOL_AVAILABLE'])
		{
			return;
		}

		$arResult['IS_TOOL_AVAILABLE'] = (new Settings())->isToolAvailable(Settings::TOOLS['effective']);
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
		static::tryParseStringParameter($arParams['DEFAULT_PAGE_SIZE'], null);
		static::tryParseStringParameter($arParams['PLATFORM'], 'web');
		static::tryParseArrayParameter($arParams['PAGE_SIZES'], null);
		static::tryParseIntegerParameter($arParams['GROUP_ID'], 0);

		$this->userId = ($this->arParams['USER_ID'] ?: User::getId());
		$this->groupId = ($this->arParams['GROUP_ID'] ?: 0);

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$this->arResult['FILTERS'] = static::getFilterList();
		$this->arResult['PRESETS'] = static::getPresetList();

		$this->arResult['tasksEfficiencyEnabled'] = Bitrix24::checkFeatureEnabled(
			Bitrix24\FeatureDictionary::TASK_EFFICIENCY
		);

		if (!$this->arResult['tasksEfficiencyEnabled'])
		{
			$this->arResult['TASK_LIMIT_EXCEEDED'] = true;

			$efficiencyData = $this->getDefaultEfficiencyData();
		}
		else
		{
			$this->arResult['TASK_LIMIT_EXCEEDED'] = false;

			$efficiencyData = (
				$this->arParams['PLATFORM'] === 'mobile'
					? $this->getEfficiencyDataForMobile($this->userId, $this->groupId)
					: $this->getEfficiencyData($this->userId)
			);
		}

		$this->arResult['JS_DATA']['userId'] = $this->userId;
		$this->arResult['JS_DATA']['efficiencyData'] = $efficiencyData;
	}

	/**
	 * @return array
	 * @throws ObjectException
	 */
	private function getDefaultEfficiencyData(): array
	{
		$date = new DateTime();
		$month = $date->getMonthGmt();
		$month = ($month < 10? '0'.$month : $month);

		return [
			'EFFICIENCY' => 75,
			'COMPLETED' => 20,
			'VIOLATIONS' => 25,
			'IN_PROGRESS' => 100,
			'GRAPH_DATA' => [
				[
					'DATE' => '2019-'.$month.'-01',
					'EFFECTIVE' => 100,
				],
				[
					'DATE' => '2019-'.$month.'-03',
					'EFFECTIVE' => 25,
				],
				[
					'DATE' => '2019-'.$month.'-06',
					'EFFECTIVE' => 50,
				],
				[
					'DATE' => '2019-'.$month.'-09',
					'EFFECTIVE' => 0,
				],
				[
					'DATE' => '2019-'.$month.'-12',
					'EFFECTIVE' => 33,
				],
				[
					'DATE' => '2019-'.$month.'-15',
					'EFFECTIVE' => 10,
				],
				[
					'DATE' => '2019-'.$month.'-18',
					'EFFECTIVE' => 100,
				],
				[
					'DATE' => '2019-'.$month.'-21',
					'EFFECTIVE' => 30,
				],
				[
					'DATE' => '2019-'.$month.'-24',
					'EFFECTIVE' => 75,
				],
				[
					'DATE' => '2019-'.$month.'-27',
					'EFFECTIVE' => 100,
				],
				[
					'DATE' => '2019-'.$month.'-30',
					'EFFECTIVE' => 25,
				],
			],
			'GRAPH_MIN_PERIOD' => 'DD',
		];
	}

	/**
	 * @return string
	 */
	public static function getFilterId(): string
	{
		return Effective::getFilterId();
	}

	/**
	 * @return array
	 */
	public static function getPresetList(): array
	{
		return Effective::getPresetList();
	}

	/**
	 * @return array
	 */
	public static function getFilterList(): array
	{
		return [
			'GROUP_ID' => [
				'id' => 'GROUP_ID',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_GROUP_ID'),
				'type' => 'custom_entity',
				'default' => true,
				'selector' => [
					'TYPE' => 'group',
					'DATA' => [
						'ID' => 'group',
						'FIELD_ID' => 'GROUP_ID'
					],
				],
			],
			'DATETIME' => [
				'id' => 'DATETIME',
				'name' => Loc::getMessage('TASKS_FILTER_COLUMN_DATE'),
				'type' => 'date',
				'default' => true,
				'exclude' => [
					Filter\DateType::NONE,
					Filter\DateType::TOMORROW,
					Filter\DateType::PREV_DAYS,
					Filter\DateType::NEXT_DAYS,
					Filter\DateType::NEXT_WEEK,
					Filter\DateType::NEXT_MONTH
				],
			],
		];
	}

	public function getEfficiencyDataForMobile(int $userId = 0, int $groupId = 0): array
	{
		$datesRange = Effective::getDatesRange();
		$dateFrom = $datesRange['FROM'];
		$dateTo = $datesRange['TO'];

		$tasksCounters = Effective::getCountersByRange($dateFrom, $dateTo, $userId, $groupId);
		$graphData = static::getGraphData($dateFrom, $dateTo, $userId, $groupId, false);

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

		if (!empty($graphData))
		{
			$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
			$graphData = array_reverse(array_slice(array_reverse($graphData), 0, 6));
			foreach ($graphData as $key => $value)
			{
				$graphData[$key]['DATE'] = FormatDate(
					$culture->getDayShortMonthFormat(),
					(new DateTime($value['DATE'], 'Y-m-d'))->getTimestamp()
				);
			}
		}

		return [
			'EFFICIENCY' => $efficiency,
			'COMPLETED' => $tasksCounters['COMPLETED'],
			'VIOLATIONS' => $tasksCounters['VIOLATIONS'],
			'IN_PROGRESS' => $tasksCounters['IN_PROGRESS'],
			'GRAPH_DATA' => $graphData,
		];
	}

	/**
	 * @param $userId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getEfficiencyData($userId): array
	{
		$filter = static::processFilter();

		$datesRange = Effective::getDatesRange();
		$dateFrom = $datesRange['FROM'];
		$dateTo = $datesRange['TO'];

		$groupId = 0;
		$groupByHour = false;

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
		if (isset($filter['::']) && $filter['::'] === 'BY_DAY')
		{
			unset($filter['::']);
			$groupByHour = true;
		}

		$tasksCounters = Effective::getCountersByRange($dateFrom, $dateTo, $userId, $groupId);
		$graphData = static::getGraphData($dateFrom, $dateTo, $userId, $groupId, $groupByHour);

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

		return [
			'EFFICIENCY' => $efficiency,
			'COMPLETED' => $tasksCounters['COMPLETED'],
			'VIOLATIONS' => $tasksCounters['VIOLATIONS'],
			'IN_PROGRESS' => $tasksCounters['IN_PROGRESS'],
			'GRAPH_DATA' => $graphData,
			'GRAPH_MIN_PERIOD' => ($groupByHour? 'hh' : 'DD'),
		];
	}

	/**
	 * @param $dateFrom
	 * @param $dateTo
	 * @param $userId
	 * @param $groupId
	 * @param $groupByHour
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getGraphData($dateFrom, $dateTo, $userId, $groupId, $groupByHour): array
	{
		$graphData = [];
		$graphDataRes = Effective::getEfficiencyForGraph($dateFrom, $dateTo, $userId, $groupId, ($groupByHour? 'HOUR' : ''));

		foreach ($graphDataRes as $row)
		{
			$row['DATE'] = ($groupByHour ? $row['HOUR'] : $row['DATE']->format('Y-m-d'));
			$row['EFFECTIVE'] = round($row['EFFECTIVE']);

			$graphData[] = $row;
		}

		return $graphData;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 */
	private static function processFilter(): array
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
						if (isset($rawFilter[$item['id']]) && $rawFilter[$item['id']])
						{
							$filter[$item['id']] = $rawFilter[$item['id']];
						}
						break;

					case 'date':
						$fromKey = $item['id'].'_from';
						$toKey = $item['id'].'_to';

						if (array_key_exists($fromKey, $rawFilter) && !empty($rawFilter[$fromKey]))
						{
							$filter['>='.$item['id']] = $rawFilter[$fromKey];
						}
						if (array_key_exists($toKey, $rawFilter) && !empty($rawFilter[$toKey]))
						{
							$filter['<='.$item['id']] = $rawFilter[$toKey];
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

	/**
	 * @param $rawFilter
	 * @param $item
	 * @param $filter
	 * @return bool
	 * @throws \Bitrix\Main\ObjectException
	 */
	private static function checkByDayFiltering($rawFilter, $item, $filter): bool
	{
		$dateTypesForDayFiltering = [
			Filter\DateType::YESTERDAY,
			Filter\DateType::CURRENT_DAY,
			Filter\DateType::EXACT,
		];
		$rangeType = Filter\DateType::RANGE;

		$dateSel = $rawFilter[$item['id'].'_datesel'];
		$dateFrom = new DateTime($filter['>='.$item['id']]);
		$dateTo = new DateTime($filter['<='.$item['id']]);

		return in_array($dateSel, $dateTypesForDayFiltering, true)
			|| ($dateSel === $rangeType && $dateFrom->format('Y-m-d') === $dateTo->format('Y-m-d'));
	}

	/**
	 * @return Filter\Options
	 */
	private static function getFilterOptions(): Filter\Options
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = new Filter\Options(static::getFilterId(), static::getPresetList());
		}

		return $instance;
	}
}
