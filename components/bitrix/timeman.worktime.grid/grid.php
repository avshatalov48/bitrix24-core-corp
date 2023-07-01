<?php
namespace Bitrix\Timeman\Component\WorktimeGrid;
require_once __DIR__ . '/ranges.php';
require_once __DIR__ . '/normalizer.php';
require_once __DIR__ . '/templateparams.php';

use Bitrix\Main;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\Quarter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Timeman;
use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\User\UserCollection;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationParams;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
Loc::loadMessages(__FILE__);

class Grid
{
	/** @var Timeman\Model\Schedule\Shift\ShiftCollection $workShifts */
	protected $workShifts = [];
	protected $pageNavigation;
	protected $pageNumber;
	protected $countRows;
	protected $pageSizes = [10, 20, 50, 100, 500];

	private $id;
	private $headers;
	private $filter;
	private $gridOptions;
	private $pageSize;
	/** @var TimeHelper */
	private $timeHelper;
	/** @var WorktimeViolationManager */
	private $violationManager;
	/** @var PageNavigation */
	private $navigation;
	private $periodSize;
	private $periodType;
	private $periodPrev;
	private $periodNext;
	private $periodDatesFormatted;
	private $options;
	private $urlManager;
	private $dateTimeFrom;
	private $dateTimeTo;
	/** @var Main\Type\Date[] */
	private $periodDateTimes = [];
	private $fromToDates = [];
	private $currentUserDate;
	private $weekStart = 1;
	/**
	 * @var array|Timeman\Model\Schedule\Violation\ViolationRules[][]
	 */
	private $userViolationRulesMap = [];
	private $timemanEnabledSettings = [];
	private $dateTimeFormat;
	/** @var Timeman\Model\User\User */
	private $currentUser;
	private $recordManagers = [];

	protected function __construct($id, $options = [])
	{
		$this->id = $id;
		$this->options = $options;
		$this->currentUser = $options['currentUser'];
		$this->weekStart = $options['weekStart'];
		$this->dateTimeFormat = $options['dateTimeFormat'];
		$this->currentUserDate = TimeHelper::getInstance()->getUserDateTimeNow($this->currentUser->getId());
		if (!array_key_exists('FILTER_FIELDS_SHOW_ALL', $this->options))
		{
			$this->options['FILTER_FIELDS_SHOW_ALL'] = true;
		}
		if (!array_key_exists('FILTER_FIELDS_SCHEDULES', $this->options))
		{
			$this->options['FILTER_FIELDS_SCHEDULES'] = true;
		}
		if (!array_key_exists('FILTER_FIELDS_REPORT_APPROVED', $this->options))
		{
			$this->options['FILTER_FIELDS_REPORT_APPROVED'] = true;
		}
		if (!array_key_exists('SHOW_USER_ABSENCES', $this->options))
		{
			$this->options['SHOW_USER_ABSENCES'] = true;
		}
		if (!array_key_exists('FILTER_FIELDS_USERS', $this->options))
		{
			$this->options['FILTER_FIELDS_USERS'] = true;
		}
		$this->timeHelper = TimeHelper::getInstance();
		$this->violationManager = DependencyManager::getInstance()->getViolationManager();
		$this->urlManager = DependencyManager::getInstance()->getUrlManager();
		$this->createPeriod();
	}

	public static function getInstance($gridId, $options = [])
	{
		return new static($gridId, $options);
	}

	/**
	 * @param array $recordsByUserDate
	 * @param Timeman\Model\Schedule\ScheduleCollection $scheduleCollection
	 * @param array $shiftPlansByUserShiftDate
	 * @param array $violationRulesMap
	 * @param array $options
	 */
	public function fillRowsDataWithTemplateParams(&$departmentToUsers, UserCollection $usersCollection, $recordsByUserDate, $scheduleCollection, $shiftPlansByUserShiftDate, $violationRulesMap, $recordManagers)
	{
		$this->userViolationRulesMap = $violationRulesMap;
		$this->recordManagers = $recordManagers;
		$absenceData = [];
		if ($this->options['SHOW_USER_ABSENCES'])
		{
			$userIds = [];
			foreach ($departmentToUsers as $departmentData)
			{
				foreach ($departmentData['USERS'] as $user)
				{
					$userIds[$user['ID']] = true;
				}
			}
			$absenceData = $this->findAbsenceData(array_keys($userIds));
		}
		foreach ($departmentToUsers as $dIndex => $departmentData)
		{
			$users = $departmentData['USERS'];
			foreach ($users as $uIndex => $userData)
			{
				if (!$user = $usersCollection->getByPrimary($userData['ID']))
				{
					continue;
				}
				foreach ($this->periodDatesFormatted as $periodDateFormatted)
				{
					$cellRecords = [];
					if (isset($recordsByUserDate[$user->getId()]) &&
						isset($recordsByUserDate[$user->getId()][$periodDateFormatted]))
					{
						$cellRecords = $recordsByUserDate[$user->getId()][$periodDateFormatted];
					}
					$sortedTemplateParamsList = $this->buildTemplateParamsForDayCell(
						$scheduleCollection,
						$this->getPeriodDateTimes()[$periodDateFormatted],
						$periodDateFormatted,
						$cellRecords,
						$shiftPlansByUserShiftDate,
						$user,
						$absenceData
					);
					$departmentToUsers[$dIndex]['USERS_DATA_BY_DATES'][$user->getId()][$periodDateFormatted] = $sortedTemplateParamsList;
				}
			}
		}
	}

	/**
	 * @param Timeman\Model\Schedule\ScheduleCollection $scheduleCollection
	 * @param \DateTime|Main\Type\Date $drawingDate
	 * @param $options
	 * @param $cellRecords
	 * @param $shiftPlansByUserShiftDate
	 * @param Timeman\Model\User\User $user
	 * @param $recordsByUserDate
	 * @return TemplateParams[]
	 */
	public function buildTemplateParamsForDayCell($scheduleCollection, $drawingDate, $periodDateFormatted,
												  $cellRecords, $shiftPlansByUserShiftDate, $user, $absenceData)
	{
		/** @var TemplateParams[] $cellTemplateParamsList */
		$cellTemplateParamsList = [];
		foreach ($scheduleCollection->getAll() as $schedule)
		{
			if (!$schedule->isShifted() ||
				($this->options['isShiftplan'] && $schedule->getId() !== $this->options['shiftplanScheduleId'])
			)
			{
				continue;
			}
			// first iterate through shifts
			foreach ($schedule->obtainShifts() as $shift)
			{
				$skipShift = $shift->isDeleted();
				// draw "add shiftplan" btn OR record OR shiftplan for each shift
				foreach ($cellRecords as $cellRecord)
				{
					/** @var WorktimeRecord $cellRecord */
					if ($cellRecord->getShiftId() === $shift->getId())
					{
						// draw record for this shift
						$skipShift = true;
						$plan = $shiftPlansByUserShiftDate[$cellRecord->getUserId()][$shift->getId()][$periodDateFormatted];
						$templateParams = $this->buildTemplateParams($user, $cellRecord, $schedule, $shift, $plan, $drawingDate);

						$this->addViolationsToTemplateParams($templateParams, $user, $absenceData);

						$cellTemplateParamsList[] = $templateParams;
					}
				}
				if ($skipShift)
				{
					continue;
				}
				//
				$plan = $shiftPlansByUserShiftDate[$user->getId()][$shift->getId()][$periodDateFormatted];
				if ($plan)
				{
					// draw shiftplan
					$templateParams = $this->buildTemplateParams($user, null, $schedule, $shift, $plan, $drawingDate);
					$cellTemplateParamsList[] = $templateParams;
				}
				elseif ($this->options['showAddShiftPlanBtn'])
				{
					// draw btn to add shiftplan for this shift
					$templateParams = $this->buildTemplateParams($user, null, $schedule, $shift, null, $drawingDate);
					$templateParams->showAddShiftPlanBtn = true;

					$cellTemplateParamsList[] = $templateParams;
				}
			}
		}

		$skipRecordIds = [];
		foreach ($cellTemplateParamsList as $cellTemplateParams)
		{
			if ($cellTemplateParams->record)
			{
				$skipRecordIds[] = $cellTemplateParams->record->getId();
			}
		}
		// then iterate through records
		foreach ($cellRecords as $record)
		{
			/** @var WorktimeRecord $record */
			if (in_array($record->getId(), $skipRecordIds, true))
			{
				continue;
			}
			$schedule = $scheduleCollection->getByPrimary($record->getScheduleId());
			$plan = $shiftPlansByUserShiftDate[$record->getUserId()][$record->getShiftId()][$periodDateFormatted] ?? null;
			$shift = null;
			if ($schedule)
			{
				$shift = $schedule->obtainShiftByPrimary($record->getShiftId());
			}
			$templateParams = $this->buildTemplateParams($user, $record, $schedule, $shift, $plan, $drawingDate);
			$this->addViolationsToTemplateParams($templateParams, $user, $absenceData);
			$cellTemplateParamsList[] = $templateParams;
		}

		// then absence data
		if ($userAbsence = $this->buildAbsenceByUserDate($user, $periodDateFormatted, $absenceData))
		{
			$templateParams = $this->buildTemplateParams($user, null, null, null, null, $drawingDate);
			$templateParams->absence = $userAbsence;
			$cellTemplateParamsList[] = $templateParams;
		}

		return $this->sortBlocksInsideCellByDate($cellTemplateParamsList, !$this->options['isShiftplan']);
	}

	/**
	 * @param TemplateParams[] $cellTemplateParamsList
	 */
	private function sortBlocksInsideCellByDate($cellTemplateParamsList, $drawAbsenceOnTopOfRecord = false)
	{
		/** @var TemplateParams[] $result */
		$result = [];
		$uniqueKeys = [];
		$absenceKey = null;
		$hasRecord = false;
		foreach ($cellTemplateParamsList as $templateParams)
		{
			$key = 0;
			if ($templateParams->record)
			{
				$hasRecord = true;
				$key = $templateParams->record->getRecordedStartTimestamp();
			}
			elseif ($templateParams->shift)
			{
				if ($templateParams->shiftPlan)
				{
					$key = $templateParams->shift->buildUtcStartByShiftplan($templateParams->shiftPlan)->getTimestamp();
				}
				else
				{
					$key = $templateParams->buildUtcShiftStart()->getTimestamp();
				}
			}
			elseif ($templateParams->absence)
			{
				$absence = $templateParams->absence;
				$key = 0;
				while (in_array($key, $uniqueKeys, true))
				{
					$key = $key + 1;
				}
				$absenceKey = $key;
			}
			while (in_array($key, $uniqueKeys, true))
			{
				$key = $key + 1;
			}
			$uniqueKeys[] = $key;
			$result[$key] = $templateParams;
		}
		ksort($result);
		if ($drawAbsenceOnTopOfRecord)
		{
			foreach ($result as $templateParams)
			{
				if ($templateParams->shiftPlan)
				{
					$drawAbsenceOnTopOfRecord = false;
					break;
				}
			}
		}
		if ($drawAbsenceOnTopOfRecord && $hasRecord && !empty($absence) && $absenceKey !== null)
		{
			foreach ($result as $templateParamsInner)
			{
				if ($templateParamsInner->record && !Schedule::isScheduleShifted($templateParamsInner->schedule))
				{
					$templateParamsInner->absence = $absence;
					unset($result[$absenceKey]);
					break;
				}
			}
		}

		return $result;
	}

	private function createPeriod($force = false)
	{
		if (!empty($this->periodDateTimes) && !$force)
		{
			return;
		}
		$this->periodDateTimes = [];
		$range = Ranges::getPeriod($this->getDateTimeFrom(), $this->getDateTimeTo());
		foreach ($range as $date)
		{
			$this->periodDateTimes[$date->format($this->dateTimeFormat)] = Main\Type\Date::createFromPhp($date);
		}

		$this->periodSize = sizeof($this->periodDateTimes);
		$this->periodType = $this->getPeriodType($this->periodDateTimes);
		$this->periodDatesFormatted = array_keys($this->periodDateTimes);

		$this->periodPrev = array_map(function ($date) {
			return Main\Type\Date::createFromTimestamp($date->getTimestamp());
		}, $this->periodShift($this->periodDateTimes, $this->periodType, true));

		$this->periodNext = array_map(function ($date) {
			return Main\Type\Date::createFromTimestamp($date->getTimestamp());
		}, $this->periodShift($this->periodDateTimes, $this->periodType, false));
	}

	public function getCurrentPeriodType()
	{
		$this->createPeriod();

		return $this->periodType;
	}

	public function getPeriodDateTimes()
	{
		return $this->periodDateTimes;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getDateTimeTo()
	{
		if ($this->dateTimeTo === null)
		{
			$this->dateTimeTo = $this->getFromToDates()['TO'];
			if (!$this->dateTimeTo)
			{
				$this->dateTimeTo = $this->getCurrentUserDate();
			}
		}
		return $this->dateTimeTo;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getDateTimeFrom()
	{
		if ($this->dateTimeFrom === null)
		{
			$this->dateTimeFrom = $this->getFromToDates()['FROM'];
			if (!$this->dateTimeFrom)
			{
				$this->dateTimeFrom = $this->getCurrentUserDate();
			}
		}
		return $this->dateTimeFrom;
	}

	public function getFilterId()
	{
		return 'TM_WORKTIME_GRID_FILTER_' . $this->id;
	}

	public function getFilter()
	{
		if (!isset($this->filter))
		{
			$filterId = $this->getFilterId();
			list($from, $to) = $this->calcDates(DateType::CURRENT_MONTH);
			$fields = [
				'REPORT_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'REPORT_PERIOD_from' => $from->format(Main\Type\Date::getFormat()),
				'REPORT_PERIOD_to' => $to->format(Main\Type\Date::getFormat()),
			];
			if ($this->options['scheduleReportPeriod'])
			{
				switch ($this->options['scheduleReportPeriod'])
				{
					case ScheduleTable::REPORT_PERIOD_WEEK:
						list($from, $to) = $this->calcDates(DateType::CURRENT_WEEK);
						$fields = [
							'REPORT_PERIOD_datesel' => DateType::CURRENT_WEEK,
							'REPORT_PERIOD_from' => $from->format(Main\Type\Date::getFormat()),
							'REPORT_PERIOD_to' => $to->format(Main\Type\Date::getFormat()),
						];
						break;
					case ScheduleTable::REPORT_PERIOD_QUARTER:
						list($from, $to) = $this->calcDates(DateType::CURRENT_QUARTER);
						$fields = [
							'REPORT_PERIOD_datesel' => DateType::CURRENT_QUARTER,
							'REPORT_PERIOD_from' => $from->format(Main\Type\Date::getFormat()),
							'REPORT_PERIOD_to' => $to->format(Main\Type\Date::getFormat()),
						];
						break;
					default:
						break;
				}
			}

			$presets = [
				'timeman_worktime_grid_filter_period' => [
					'name' => Loc::getMessage('TM_WORKTIME_GRID_FILTER_PRESET_REPORT_PERIOD'),
					'default' => true,
					'fields' => $fields,
				],
			];
			$this->filter = [
				'ID' => $filterId,
				'FIELDS' => [
					[
						'id' => 'REPORT_PERIOD',
						'name' => Loc::getMessage('TM_WORKTIME_GRID_FILTER_PRESET_REPORT_PERIOD'),
						'type' => 'date',
						'required' => true,
						'default' => true,
						'exclude' => [
							DateType::PREV_DAYS,
							DateType::YEAR,
							DateType::NONE,
							DateType::YESTERDAY,
							DateType::CURRENT_DAY,
							DateType::TOMORROW,
							DateType::NEXT_DAYS,
							DateType::EXACT,
							DateType::NEXT_WEEK,
							DateType::NEXT_MONTH,
						],
					],
				],
				'PRESETS' => $presets,
			];
			if ($this->options['hasAccessToOtherWorktime'])
			{
				if ($this->options['FILTER_FIELDS_USERS'])
				{
					$this->filter['FIELDS'][] = [
						'id' => 'USERS_DEPARTMENTS',
						'name' => Loc::getMessage('TM_GRID_HEADER_USERS_DEPARTMENTS_LABEL'),
						'type' => 'dest_selector',
						'default' => true,
						'params' => [
							'apiVersion' => '3',
							'context' => $this->id . '_USERS_DEPARTMENTS',
							'multiple' => 'Y',
							'contextCode' => 'U',
							'enableAll' => 'N',
							'departmentSelectDisable' => 'N',
							'enableSonetgroups' => 'N',
							'enableUsers' => 'Y',
							'allowEmailInvitation' => 'N',
						],
					];
				}
				if ($this->options['FILTER_FIELDS_SHOW_ALL'])
				{
					$this->filter['FIELDS'][] = [
						'id' => 'SHOW_ALL',
						'name' => Loc::getMessage('TM_REPORT_FILTER_SHOW_ALL_LABEL'),
						'type' => 'list',
						'items' => [
							'N' => Loc::getMessage('TM_REPORT_FILTER_SHOW_ALL_N'),
							'Y' => Loc::getMessage('TM_REPORT_FILTER_SHOW_ALL_Y'),
						],
						'default' => true,
					];
				}
				if ($this->options['FILTER_FIELDS_REPORT_APPROVED'])
				{
					$this->filter['FIELDS'][] = [
						'id' => 'IS_REPORT_APPROVED',
						'name' => Loc::getMessage('TM_REPORT_FILTER_IS_REPORT_APPROVED_LABEL'),
						'type' => 'list',
						'items' => [
							'Y' => Loc::getMessage('TM_REPORT_FILTER_IS_REPORT_APPROVED_YES'),
							'N' => Loc::getMessage('TM_REPORT_FILTER_IS_REPORT_APPROVED_NO'),
						],
						'default' => true,
					];
				}
				if ($this->options['FILTER_FIELDS_SHIFTS_EXISTENCE'] ?? false)
				{
					$this->filter['FIELDS'][] = [
						'id' => 'SHIFTS_EXISTENCE',
						'name' => Loc::getMessage('TM_REPORT_FILTER_SHOW_ALL_LABEL'),
						'type' => 'list',
						'items' => [
							'N' => Loc::getMessage('TM_REPORT_FILTER_SHOW_ALL_Y'),
							'Y' => Loc::getMessage('TM_REPORT_FILTER_SHIFTS_EXISTENCE_HAS_SHIFTS'),
						],
						'default' => true,
					];
				}
				if ($this->options['FILTER_FIELDS_SCHEDULES'])
				{
					$schedules = DependencyManager::getInstance()
						->getScheduleRepository()
						->getActiveSchedulesQuery()
						->addSelect('ID')
						->addSelect('NAME')
						->exec()
						->fetchAll();
					if (!empty($schedules))
					{
						$this->filter['FIELDS'][] = [
							'id' => 'SCHEDULES',
							'name' => Loc::getMessage('TM_REPORT_FILTER_SCHEDULES_LABEL'),
							'params' => ['multiple' => 'Y'],
							'type' => 'list',
							'items' => array_combine(array_column($schedules, 'ID'), array_column($schedules, 'NAME')),
							'default' => true,
						];
					}
				}
			}

			$options = new Main\UI\Filter\Options(
				$this->filter['ID'],
				$this->filter['PRESETS']
			);

			$this->filter['DATA'] = $options->getFilter($this->filter['FIELDS']) ?: [];

			$curPresets = $options->getPresets();
			if (!empty($curPresets['timeman_worktime_grid_filter_period']['fields']['REPORT_PERIOD_datesel'])
				&& !empty($presets['timeman_worktime_grid_filter_period']['fields']['REPORT_PERIOD_datesel'])
				&& $curPresets['timeman_worktime_grid_filter_period']['fields']['REPORT_PERIOD_datesel'] !== $presets['timeman_worktime_grid_filter_period']['fields']['REPORT_PERIOD_datesel']
			)
			{
				$options->setPresets($presets);
				$options->save();
			}
			$this->filter['DATA'] = $options->getFilter($this->filter['FIELDS']) ?: [];

		}

		return $this->filter;
	}

	public function getShifts()
	{
		return $this->workShifts;
	}

	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getPeriodNext()
	{
		$this->createPeriod();
		return $this->periodNext;
	}

	/**
	 * @return mixed
	 */
	public function getPeriodPrev()
	{
		$this->createPeriod();
		return $this->periodPrev;
	}

	public function getPeriodDates()
	{
		$res = [];
		foreach ($this->periodDatesFormatted as $date)
		{
			$res[$date] = $this->getPeriodDateTimes()[$date];
		}
		return $res;
	}

	public function getHeaders()
	{
		if ($this->headers === null)
		{
			$this->headers = [];
			$expectedStickedColumns = ['USER_NAME',];
			$this->headers[] = [
				'id' => 'USER_NAME',
				'name' => Loc::getMessage('TM_GRID_HEADER_USERS_LABEL'),
				'default' => true,
				'sticked' => true,
				'class' => 'js-tm-fixed-columns',
				'width' => 250,
			];
			if ($this->options['SHOW_STATS_COLUMNS'])
			{
				$this->headers[] = [
					'id' => 'WORKED_DAYS',
					'name' => Loc::getMessage('TM_GRID_HEADER_TITLE_WORKED_DAYS'),
					'default' => true,
					'sticked' => true,
					'class' => 'js-tm-fixed-columns main-grid-cell-head-stat',
					'width' => 120,
				];
				$this->headers[] = [
					'id' => 'WORKED_HOURS',
					'name' => Loc::getMessage('TM_GRID_HEADER_TITLE_WORKED_HOURS'),
					'default' => true,
					'sticked' => true,
					'class' => 'js-tm-fixed-columns main-grid-cell-head-stat',
					'width' => 120,
				];
				$this->headers[] = [
					'id' => 'PERCENTAGE_OF_VIOLATIONS',
					'name' => Loc::getMessage('TM_GRID_HEADER_PERCENTAGE_OF_VIOLATIONS'),
					'default' => true,
					'sticked' => true,
					'class' => 'js-tm-fixed-columns main-grid-cell-head-stat',
					'width' => 120,
				];
				$expectedStickedColumns = array_merge($expectedStickedColumns, ['WORKED_DAYS', 'WORKED_HOURS', 'PERCENTAGE_OF_VIOLATIONS']);
			}
			$currentColumns = (array)$this->getGridOptions()->getStickedColumns();
			sort($currentColumns);
			sort($expectedStickedColumns);
			if ($currentColumns !== $expectedStickedColumns)
			{
				$this->getGridOptions()->setStickedColumns($expectedStickedColumns);
				$this->getGridOptions()->save();
			}
			$headerDateFormat = Loc::getMessage('TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_WEEK_DAY');
			if (empty($headerDateFormat))
			{
				$headerDateFormat = 'D d';
			}
			if ($this->getDateTimeFrom() && $this->getDateTimeTo())
			{
				if ($this->getDateTimeFrom()->format('n') !== $this->getDateTimeTo()->format('n'))
				{
					$headerDateFormat = Loc::getMessage('TIMEMAN_WORKTIME_GRID_COLUMNS_DATE_FORMAT_DAY_WEEK_DAY_MONTH');
					if (empty($headerDateFormat))
					{
						$headerDateFormat = 'D d M';
					}
				}
			}
			$userNow = TimeHelper::getInstance()->getUserDateTimeNow($this->currentUser->getId());
			$userNow = $userNow ? $userNow->format($this->dateTimeFormat) : '';
			foreach ($this->periodDatesFormatted as $date)
			{
				$item = [
					'id' => $date,
					'name' => $this->timeHelper->formatDateTime($this->getPeriodDateTimes()[$date], $headerDateFormat),
					'date' => $this->getPeriodDateTimes()[$date],
					'default' => true,
					'class' => '',
				];
				if ($this->getPeriodDateTimes()[$date]->format($this->dateTimeFormat) == $userNow)
				{
					$item['class'] .= ' js-tm-header-today timeman-grid-column-header-today';
				}
				$this->headers[] = $item;
			}
		}

		return $this->headers;
	}

	public function getWorktimeStatistics($departmentUsersData)
	{
		$statsResult = [];
		foreach ($departmentUsersData as $departmentData)
		{
			foreach ((array)$departmentData['USERS_DATA_BY_DATES'] as $userId => $rowData)
			{
				$statsResult[$userId] = [
					'TOTAL_WORKED_SECONDS' => 0,
					'TOTAL_WORKDAYS' => 0,
					'TOTAL_NOT_APPROVED_WORKDAYS' => 0,
					'TOTAL_VIOLATIONS' => [
						'PERSONAL' => 0,
						'COMMON' => 0,
					],
				];
				foreach ($rowData as $templateParamsList)
				{
					foreach ((array)$templateParamsList as $templateParams)
					{
						/** @var TemplateParams $templateParams */
						if (!$templateParams->record)
						{
							continue;
						}
						$record = $templateParams->record;

						$dayIsEnded = $record->getRecordedStopTimestamp() > 0;
						$dayIsApproved = $record->isApproved();
						$expired = $templateParams->isRecordExpired();
						if (!$dayIsApproved || $expired)
						{
							$statsResult[$userId]['TOTAL_NOT_APPROVED_WORKDAYS']++;
						}
						else
						{
							$statsResult[$userId]['TOTAL_WORKDAYS']++;
							$statsResult[$userId]['TOTAL_WORKED_SECONDS'] += $record->calculateCurrentDuration();
						}
						if ($dayIsEnded && $dayIsApproved)
						{
							if (!empty($templateParams->noticesIndividual))
							{
								$statsResult[$userId]['TOTAL_VIOLATIONS']['PERSONAL'] += 1;
							}
							if (!empty($templateParams->noticesCommon))
							{
								$statsResult[$userId]['TOTAL_VIOLATIONS']['COMMON'] += 1;
							}
						}
					}
				}
			}
		}
		return $statsResult;
	}

	public function getDepartmentCodes()
	{
		$result = [];
		if (
			$this->getFilter()['DATA']
			&& !empty($this->getFilter()['DATA']['USERS_DEPARTMENTS']))
		{
			foreach ($this->getFilter()['DATA']['USERS_DEPARTMENTS'] as $departmentCode)
			{
				if (EntityCodesHelper::isDepartment($departmentCode))
				{
					$result[] = $departmentCode;
				}
			}
		}
		return $result;
	}

	public function showWithShiftPlansOnly()
	{
		return $this->getFilter()
			&& $this->getFilter()['DATA']
			&& ($this->getFilter()['DATA']['SHIFTS_EXISTENCE'] ?? '') === 'Y'
		;
	}

	public function isUserFilterApplied()
	{
		return !empty($this->getUserCodes());
	}

	public function isDepartmentFilterApplied()
	{
		return !empty($this->getDepartmentCodes());
	}

	public function getFilteredSchedulesIds()
	{
		if ($this->getFilter()['DATA'] && !empty($this->getFilter()['DATA']['SCHEDULES']))
		{
			return array_map('intval', $this->getFilter()['DATA']['SCHEDULES']);
		}
		return [];
	}

	public function isSchedulesFilterApplied()
	{
		return !empty($this->getFilteredSchedulesIds());
	}

	public function getUserCodes()
	{
		$userCodes = [];
		if (
			$this->getFilter()['DATA']
			&& ($this->getFilter()['DATA']['USERS_DEPARTMENTS'] ?? [])
		)
		{
			foreach ($this->getFilter()['DATA']['USERS_DEPARTMENTS'] as $userCode)
			{
				if (EntityCodesHelper::isUser($userCode))
				{
					$userCodes[] = $userCode;
				}
			}
		}
		return $userCodes;
	}

	public function isShowUsersWithRecordsOnly()
	{
		return $this->getFilter()
			&& $this->getFilter()['DATA']
			&& ($this->getFilter()['DATA']['SHOW_ALL'] ?? '') === 'N'
		;
	}

	public function getFilterFindText()
	{
		if ($this->getFilter() &&
			$this->getFilter()['DATA'] &&
			$this->getFilter()['DATA']['FIND'])
		{
			return $this->getFilter()['DATA']['FIND'];
		}
		return null;
	}

	public function getFilterByApproved()
	{
		if (
			$this->getFilter()
			&& $this->getFilter()['DATA']
			&& ($this->getFilter()['DATA']['IS_REPORT_APPROVED'] ?? null)
		)
		{
			return $this->getFilter()['DATA']['IS_REPORT_APPROVED'];
		}

		return null;
	}

	private function getFilterPeriodType()
	{
		if ($this->getFilter() && !empty($this->getFilter()['DATA'])
			&& !empty($this->getFilter()['DATA']['REPORT_PERIOD_datesel']))
		{
			return $this->getFilter()['DATA']['REPORT_PERIOD_datesel'];
		}
		return '';
	}

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule $schedule
	 * @param Shift $shift
	 * @param array $absenceData
	 * @return array
	 */
	private function buildViolations($record, $schedule, $shift, $absenceData, $plan)
	{
		if (!$record || !$schedule || !$this->isTimemanEnabled($record->getUserId()))
		{
			return [];
		}
		$personal = $this->violationManager
			->buildViolations((new WorktimeViolationParams())
				->setShift($shift)
				->setShiftPlan($plan)
				->setSchedule($schedule)
				->setViolationRules($this->getViolationRulesByUser($record))
				->setRecord($record)
				->setAbsenceData($absenceData));
		$common = $this->violationManager
			->buildViolations((new WorktimeViolationParams())
				->setShift($shift)
				->setShiftPlan($plan)
				->setSchedule($schedule)
				->setViolationRules($schedule->obtainScheduleViolationRules())
				->setRecord($record)
				->setAbsenceData($absenceData));
		return array_merge($personal, $common);
	}

	/**
	 * @param $record
	 * @param Schedule $schedule
	 * @return array|Timeman\Model\Schedule\Violation\ViolationRules|null
	 */
	private function getViolationRulesByUser($record)
	{
		if (!empty($this->userViolationRulesMap[$record['USER_ID']]))
		{
			foreach ($this->userViolationRulesMap[$record['USER_ID']] as $vioRules)
			{
				if ((int)$vioRules->getScheduleId() === (int)$record['SCHEDULE_ID'])
				{
					return $vioRules;
				}
			}
		}
		return null;
	}

	public static function getPageSize()
	{
		return 20;
	}

	public static function getPageSizes()
	{
		$res = [];
		foreach ([5, 10, 15, 20, 30, 50] as $index)
		{
			$res[] = ['NAME' => $index, 'VALUE' => $index];
		}
		return $res;
	}

	public function getGridOptions()
	{
		return $this->gridOptions = $this->gridOptions ?: new Options($this->id);
	}

	public function getNavigation()
	{
		if (!$this->navigation)
		{
			$navData = $this->getGridOptions()->getNavParams(['nPageSize' => 25]);
			$this->navigation = new Main\UI\PageNavigation($this->id . '_navigation');
			$this->navigation
				->setPageSize($navData['nPageSize'])
				->setPageSizes(static::getPageSizes())
				->allowAllRecords(false)
				->initFromUri();
		}
		return $this->navigation;
	}

	/**
	 * Period shift and return left and right borders of new period
	 * @return []
	 */
	protected function periodShift($period, $periodType, $negative = false)
	{
		$dateFrom = clone reset($period);
		$dateTo = clone end($period);
		$count = sizeof($period);

		switch ($periodType)
		{
			case 'week':
				$dateFrom->add(($negative ? '-' : '') . '7 day');

				return Ranges::getRange('week', $dateFrom);

			case 'two_weeks':
				$dateFrom->add(($negative ? '-' : '') . '14 day');

				return Ranges::getRange('two_weeks', $dateFrom);

			case 'month':
				$dateFrom->add(($negative ? '-' : '') . '1 month');

				return Ranges::getRange('month', $dateFrom);

			case 'quarter':
				$dateFrom->add(($negative ? '-' : '') . '3 months');

				return Ranges::getRange('quarter', $dateFrom);

			case 'year':
				$dateFrom->add(($negative ? '-' : '') . '1 year');

				return Ranges::getRange('year', $dateFrom);
		}

		return [
			$dateFrom->add(($negative ? '-' : '') . $count . ' day'),
			$dateTo->add(($negative ? '-' : '') . $count . ' day'),
		];
	}

	protected function getPeriodType($period)
	{
		$dateFrom = reset($period);
		$dateTo = clone end($period);
		$count = sizeof($period);

		if ($dateFrom->format('w') == 1 && $dateTo->format('w') == 0 && $count == 7)
		{
			return 'week';
		}

		if ($dateFrom->format('w') == 1 && $dateTo->format('w') == 0 && $count == 14)
		{
			return 'two_weeks';
		}

		if ($dateFrom->format('d') == 1 && $dateTo->format('t') == $count)
		{
			return 'month';
		}

		if ($dateFrom->format('dm') == '0101' && $dateTo->format('dm') == '3112' && $dateFrom->format('Y') == $dateTo->format('Y'))
		{
			return 'year';
		}

		$quarter = Quarter::get($dateFrom);
		$year = $dateFrom->format('Y');

		if (Quarter::getStartDate($quarter, $year) == (string)$dateFrom
			&& Quarter::getEndDate($quarter, $year) == (string)$dateTo->add('1 day')
		)
		{
			return 'quarter';
		}

		return 'other';
	}

	private function getFromToDates()
	{
		if (empty($this->fromToDates))
		{
			list($this->fromToDates['FROM'], $this->fromToDates['TO']) = $this->calcDates($this->getFilterPeriodType());
		}
		return $this->fromToDates;
	}

	/**
	 * @param $filterType
	 * @return \DateTime[]
	 * @throws \Exception
	 */
	private function calcDates($filterType)
	{
		$resultFromDate = null;
		$resultToDate = null;
		switch ($filterType)
		{
			case DateType::CURRENT_WEEK:
				$dateTimeFrom = $this->getCurrentUserDate();
				if (mb_strtolower($dateTimeFrom->format('l')) !== $this->getFirstWeekDayWord())
				{
					$dateTimeFrom->modify('last ' . $this->getFirstWeekDayWord());
				}
				else
				{
					$dateTimeFrom->modify($this->getFirstWeekDayWord() . ' this week');
				}
				$dateTimeFrom->modify('midnight');

				$resultFromDate = $dateTimeFrom;

				$dateTimeTo = clone $dateTimeFrom;
				$dateTimeTo->add(new \DateInterval('P7D'));
				$dateTimeTo->sub(new \DateInterval('PT1S'));
				$resultToDate = $dateTimeTo;
				break;

			case DateType::CURRENT_MONTH:
				$dateTimeFrom = $this->getCurrentUserDate();
				$dateTimeFrom->modify('first day of this month');
				$dateTimeFrom->modify('midnight');

				$resultFromDate = $dateTimeFrom;

				$dateTimeTo = clone $dateTimeFrom;
				$dateTimeTo->modify('last day of this month');
				$dateTimeTo->setTime(23, 59, 59);
				$resultToDate = $dateTimeTo;
				break;

			case DateType::NEXT_MONTH:
				$dateTimeFrom = $this->getCurrentUserDate();
				$dateTimeFrom->modify('first day of next month');
				$dateTimeFrom->modify('midnight');

				$resultFromDate = $dateTimeFrom;

				$dateTimeTo = clone $dateTimeFrom;
				$dateTimeTo->modify('last day of next month');
				$dateTimeTo->setTime(23, 59, 59);
				$resultToDate = $dateTimeTo;
				break;

			case DateType::CURRENT_QUARTER:
				list($from, $to) = Ranges::getQuarterRange($this->getCurrentUserDate());
				$resultFromDate = $from;
				$resultToDate = $to;
				break;

			case DateType::LAST_7_DAYS:
				list($resultFromDate, $resultToDate) = $this->buildLastDaysDates(7);
				break;
			case DateType::LAST_30_DAYS:
				list($resultFromDate, $resultToDate) = $this->buildLastDaysDates(30);
				break;
			case DateType::LAST_60_DAYS:
				list($resultFromDate, $resultToDate) = $this->buildLastDaysDates(60);
				break;
			case DateType::LAST_90_DAYS:
				list($resultFromDate, $resultToDate) = $this->buildLastDaysDates(90);
				break;
			case DateType::MONTH:
				$month = $this->getFilter()['DATA']['REPORT_PERIOD_month'];
				$year = $this->getFilter()['DATA']['REPORT_PERIOD_year'];

				if (empty($month))
				{
					$month = $this->getCurrentUserDate()->format('n');
				}
				if (empty($year))
				{
					$year = $this->getCurrentUserDate()->format('Y');
				}
				$dateTimeFrom = TimeHelper::getInstance()->createUserDateTimeFromFormat(
					'Y-m-d H:i:s',
					$year . '-' . $month . '-1 00:00:00',
					$this->currentUser->getId()
				);
				$resultFromDate = $dateTimeFrom;
				$dateTo = clone $dateTimeFrom;
				$dateTo->modify('next month');
				$dateTo->sub(new \DateInterval('PT1S'));
				$resultToDate = $dateTo;
				break;
			case DateType::QUARTER:
				list($from, $to) = Ranges::getQuarterRange($this->getCurrentUserDate(), $this->getFilter()['DATA']['REPORT_PERIOD_quarter']);
				if (!empty($this->getFilter()['DATA']['REPORT_PERIOD_year']))
				{
					$from->setDate($this->getFilter()['DATA']['REPORT_PERIOD_year'], $from->format('n'), $from->format('d'));
					$to->setDate($this->getFilter()['DATA']['REPORT_PERIOD_year'], $to->format('n'), $to->format('d'));
				}
				$resultFromDate = $from;
				$resultToDate = $to;
				break;

			case DateType::LAST_WEEK:
				$dateTimeFrom = $this->getCurrentUserDate();
				$dateTimeFrom->modify($this->getFirstWeekDayWord() . ' previous week');
				$dateTimeFrom->modify('midnight');

				$resultFromDate = $dateTimeFrom;

				$dateTimeTo = clone $dateTimeFrom;
				$dateTimeTo->add(new \DateInterval('P7D'));
				$dateTimeTo->sub(new \DateInterval('PT1S'));
				$resultToDate = $dateTimeTo;
				break;

			case DateType::LAST_MONTH:
				$dateTimeFrom = $this->getCurrentUserDate();
				$dateTimeFrom->modify('first day of previous month');
				$dateTimeFrom->modify('midnight');

				$resultFromDate = $dateTimeFrom;

				$dateTimeTo = clone $dateTimeFrom;
				$dateTimeTo->add(new \DateInterval('P1M'));
				$dateTimeTo->sub(new \DateInterval('PT1S'));
				$resultToDate = $dateTimeTo;
				break;

			case DateType::RANGE:
				$startSourceDate = $this->getFilter()['DATA']['REPORT_PERIOD_from'];
				$endSourceDate = $this->getFilter()['DATA']['REPORT_PERIOD_to'];

				$resultFromDate = TimeHelper::getInstance()->createUserDateTimeFromFormat(
					'U',
					TimeHelper::getInstance()->getTimestampByUserDate(
						$startSourceDate,
						$this->currentUser->getId()
					),
					$this->currentUser->getId()
				);
				$resultToDate = TimeHelper::getInstance()->createUserDateTimeFromFormat(
					'U',
					TimeHelper::getInstance()->getTimestampByUserDate(
						$endSourceDate,
						$this->currentUser->getId()
					),
					$this->currentUser->getId()
				);
				break;
			default:
				$dateTimeFrom = $this->getCurrentUserDate();
				$dateTimeFrom->modify('first day of this month');
				$dateTimeFrom->modify('midnight');

				$resultFromDate = $dateTimeFrom;

				$dateTimeTo = clone $dateTimeFrom;
				$dateTimeTo->modify('last day of this month');
				$dateTimeTo->setTime(23, 59, 59);
				$resultToDate = $dateTimeTo;
				break;
		}
		if ($resultToDate)
		{
			$resultToDate->setTime(23, 59, 59);
		}
		return [$resultFromDate, $resultToDate];
	}

	private function buildLastDaysDates($days)
	{
		$dateTime = $this->getCurrentUserDate();
		$dateTimeFrom = clone $dateTime;
		$dateTimeFrom->sub(new \DateInterval('P' . $days . 'D'));
		$dateTimeFrom->setTime(0, 0, 0);
		$dateTimeTo = clone $dateTimeFrom;
		$dateTimeTo->add(new \DateInterval('P' . $days . 'D'));
		$dateTimeTo->sub(new \DateInterval('PT1S'));
		$dateTimeTo->setTime(23, 59, 59);

		return [$dateTimeFrom, $dateTimeTo];
	}

	private function getCurrentUserDate()
	{
		return clone $this->currentUserDate;
	}

	private function getFirstWeekDayWord()
	{
		switch ($this->weekStart)
		{
			case 0:
				return 'sunday';
			case 1:
				return 'monday';
			case 2:
				return 'tuesday';
			case 3:
				return 'wednesday';
			case 4:
				return 'thursday';
			case 5:
				return 'friday';
			case 6:
				return 'saturday';
			default:
				return 'monday';
		}
	}

	public function findAbsenceData($userIds)
	{
		if (empty($userIds))
		{
			return [];
		}
		return DependencyManager::getInstance()
			->getAbsenceRepository()
			->findAbsences(
				convertTimeStamp($this->getPeriodDateTimes()[reset($this->periodDatesFormatted)]->format('U'), 'FULL'),
				convertTimeStamp($this->getPeriodDateTimes()[end($this->periodDatesFormatted)]->format('U'), 'FULL'),
				$userIds
			);
	}

	public function isFilterByApprovedApplied()
	{
		return in_array($this->getFilterByApproved(), ['Y', 'N'], true);
	}

	private function isTimemanEnabled($userId)
	{
		if (($this->timemanEnabledSettings[$userId] ?? null) === null)
		{
			$timemanUser = new \CTimeManUser($userId);
			$settings = $timemanUser->getSettings(['UF_TIMEMAN']);
			$this->timemanEnabledSettings[$userId] = true;
			if ($settings['UF_TIMEMAN'] === false)
			{
				$this->timemanEnabledSettings[$userId] = false;
			}
		}

		return $this->timemanEnabledSettings[$userId];
	}

	private function buildAbsenceByUserDate($user, $periodDateFormatted, &$absenceData)
	{
		static $drawnAbsences = [];
		if (($drawnAbsences[$user['ID']] ?? null) === null)
		{
			$drawnAbsences[$user['ID']] = [];
		}

		$absenceUserData = (array) ($absenceData[$user['ID']] ?? []);

		foreach ($absenceUserData as $absIndex => $absenceItem)
		{
			$absItem = [];
			/*-*/
			if ($absenceItem['tm_absStartDateTime'] instanceof \DateTime && $absenceItem['tm_absStartFormatted'] === null)
			{
				$absenceData[$user['ID']][$absIndex]['tm_absStartFormatted'] = '';
				$startDateTime = clone $absenceItem['tm_absStartDateTime'];
				$startDateTime = TemplateParams::buildDateInShowingTimezone($startDateTime->getTimestamp(), $user['ID'], $this->currentUser->getId());
				if ($startDateTime)
				{
					$absenceItem['tm_absStartFormatted'] = $startDateTime->format($this->dateTimeFormat);
					$absenceData[$user['ID']][$absIndex]['tm_absStartFormatted'] = $absenceItem['tm_absStartFormatted'];
				}
			}
			if ($absenceItem['tm_absEndDateTime'] instanceof \DateTime && $absenceItem['tm_absEndFormatted'] === null)
			{
				$absenceData[$user['ID']][$absIndex]['tm_absEndFormatted'] = '';
				$endDateTime = clone $absenceItem['tm_absEndDateTime'];
				$endDateTime = TemplateParams::buildDateInShowingTimezone($endDateTime->getTimestamp(), $user['ID'], $this->currentUser->getId());
				if ($endDateTime)
				{
					$absenceItem['tm_absEndFormatted'] = $endDateTime->format($this->dateTimeFormat);
					$absenceData[$user['ID']][$absIndex]['tm_absEndFormatted'] = $absenceItem['tm_absEndFormatted'];
				}
			}
			if ($absenceItem['tm_absStartFormatted'] === $periodDateFormatted && $absenceItem['tm_absEndFormatted'] === $periodDateFormatted)
			{
				$absItem['ABSENCE_PART'] = 'full';
			}
			elseif ($absenceItem['tm_absStartFormatted'] === $periodDateFormatted)
			{
				$absItem['ABSENCE_PART'] = 'start';
			}
			elseif ($absenceItem['tm_absEndFormatted'] === $periodDateFormatted)
			{
				$absItem['ABSENCE_PART'] = 'end';
			}
			else
			{
				$periodDateTime = $this->getPeriodDateTimes()[$periodDateFormatted];
				if ($absenceItem['tm_absStartDateTime'] && $absenceItem['tm_absEndDateTime'] && $periodDateTime
					&& $absenceItem['tm_absStartDateTime']->getTimestamp() < $periodDateTime->getTimestamp()
					&& $absenceItem['tm_absEndDateTime']->getTimestamp() > $periodDateTime->getTimestamp())
				{
					$absItem['ABSENCE_PART'] = 'middle';
				}
			}
			if (!empty($absItem['ABSENCE_PART']))
			{
				$absItem['ABSENCE_HINT'] = $absenceItem['NAME'] . ' (' . $absenceItem['DATE_ACTIVE_FROM'] . ' - ' . $absenceItem['DATE_ACTIVE_TO'] . ')';
				if (!isset($drawnAbsences[$user['ID']][$absenceItem['ID']]) && $this->options['drawAbsenceTitle'])
				{
					$absItem['ABSENCE_TITLE'] = $absItem['ABSENCE_HINT'];
				}
				$drawnAbsences[$user['ID']][$absenceItem['ID']] = true;

				return $absItem;
			}
		}

		return null;
	}

	/**
	 * @param TemplateParams $templateParams
	 * @param $recordUser
	 * @param $absenceData
	 */
	public function addViolationsToTemplateParams($templateParams, $recordUser, &$absenceData)
	{
		if (!$templateParams->record)
		{
			return;
		}
		if ($absenceData === null)
		{
			$absenceData = $this->findAbsenceData([$recordUser['ID']]);
		}
		$violations = $this->buildViolations($templateParams->record, $templateParams->schedule, $templateParams->shift, $absenceData, $templateParams->shiftPlan);
		$templateParams->setViolations($violations, $recordUser['PERSONAL_GENDER']);
	}

	private function buildTemplateParams(Timeman\Model\User\User $user, ?WorktimeRecord $record, ?Schedule $schedule, ?Shift $shift, ?ShiftPlan $plan, $drawingDate)
	{
		$recordManager = null;
		if ($record)
		{
			if (($this->recordManagers[$record->getId()] ?? null) === null)
			{
				$this->recordManagers[$record->getId()] = DependencyManager::getInstance()
					->buildWorktimeRecordManager(
						$record,
						$schedule,
						$shift
					);
			}
			$recordManager = $this->recordManagers[$record->getId()];
		}
		return new TemplateParams($user, $this->currentUser, $recordManager, $schedule, $shift, $plan, $drawingDate, $this->options['isShiftplan']);
	}

	public function getUserToShowWorktime()
	{
		return $this->getUsersRequestParam();
	}

	private function getUsersRequestParam()
	{
		return Main\Application::getInstance()->getContext()->getRequest()->get('USERS');
	}

	public function isUsersWorktimeShowing()
	{
		$result = $this->getUsersRequestParam();
		return !empty($result) && EntityCodesHelper::isUser($result);
	}

	public function anyFilterApplied()
	{
		return !empty(Main\Application::getInstance()->getContext()->getRequest()->get('USERS'))
			   || $this->isUserFilterApplied()
			   || $this->isDepartmentFilterApplied()
			   || $this->isShowUsersWithRecordsOnly()
			   || $this->isFilterByApprovedApplied()
			   || !empty($this->getFilterFindText())
			   || $this->showWithShiftPlansOnly()
			   || $this->isSchedulesFilterApplied();
	}
}