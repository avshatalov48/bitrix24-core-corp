<?php
namespace Bitrix\Timeman\Component\WorktimeGrid;
require_once __DIR__ . '/ranges.php';
require_once __DIR__ . '/normalizer.php';

use Bitrix\Main;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\Quarter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Timeman;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationParams;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
Loc::loadMessages(__FILE__);

class Grid
{
	/** @var Timeman\Model\Schedule\Shift\EO_Shift_Collection $workShifts */
	protected $workShifts = [];
	/** @var Timeman\Model\Schedule\EO_Schedule_Collection */
	protected $schedules = [];
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
	private $periodDates;
	private $options;
	private $urlManager;
	private $dateTimeFrom;
	private $dateTimeTo;
	private $currentUserId;
	private $period = [];
	private $fromToDates = [];
	private $currentUserDate;
	private $weekStart = 1;
	/**
	 * @var array|Timeman\Model\Schedule\Violation\ViolationRules[][]
	 */
	private $userViolationRulesMap = [];
	private $timemanEnabledSettings = [];

	protected function __construct($id, $options = [])
	{
		$this->id = $id;
		$this->options = $options;
		$this->weekStart = $options['WEEK_START'];
		$this->currentUserId = $this->options['CURRENT_USER_ID'];
		$this->currentUserDate = TimeHelper::getInstance()->getUserDateTimeNow($this->currentUserId);
		if (!array_key_exists('FILTER_FIELDS_SHOW_ALL', $this->options))
		{
			$this->options['FILTER_FIELDS_SHOW_ALL'] = true;
		}
		if (!array_key_exists('FILTER_FIELDS_REPORT_APPROVED', $this->options))
		{
			$this->options['FILTER_FIELDS_REPORT_APPROVED'] = true;
		}
		if (!array_key_exists('SHOW_USER_ABSENCES', $this->options))
		{
			$this->options['SHOW_USER_ABSENCES'] = false;
		}
		$this->timeHelper = TimeHelper::getInstance();
		$this->violationManager = DependencyManager::getInstance()->getViolationManager();
		$this->urlManager = DependencyManager::getInstance()->getUrlManager();
		$this->createPeriod();
	}

	public function setSchedules($schedules)
	{
		$this->schedules = $schedules;
	}

	public static function getInstance($gridId, $options = [])
	{
		return new static($gridId, $options);
	}

	/**
	 * @param mixed $dateTimeFrom
	 * @return Grid
	 */
	public function setDateTimeFrom($dateTimeFrom)
	{
		$this->dateTimeFrom = $dateTimeFrom;
		$this->createPeriod(true);
		return $this;
	}

	/**
	 * @param mixed $dateTimeTo
	 * @return Grid
	 */
	public function setDateTimeTo($dateTimeTo)
	{
		$this->dateTimeTo = $dateTimeTo;
		$this->createPeriod(true);
		return $this;
	}

	private function createPeriod($force = false)
	{
		if (!empty($this->period) && !$force)
		{
			return;
		}
		$this->period = [];
		$range = Ranges::getPeriod($this->getDateTimeFrom(), $this->getDateTimeTo());
		foreach ($range as $date)
		{
			$this->period[$date->format('d.m.Y')] = Main\Type\Date::createFromPhp($date);
		}

		$this->periodSize = sizeof($this->period);
		$this->periodType = $this->getPeriodType($this->period);
		$this->periodDates = array_keys($this->period);

		$this->periodPrev = array_map(function ($date) {
			return Main\Type\Date::createFromTimestamp($date->getTimestamp());
		}, $this->periodShift($this->period, $this->periodType, true));

		$this->periodNext = array_map(function ($date) {
			return Main\Type\Date::createFromTimestamp($date->getTimestamp());
		}, $this->periodShift($this->period, $this->periodType, false));
	}

	public function getCurrentPeriodType()
	{
		$this->createPeriod();

		return $this->periodType;
	}

	public function getPeriod()
	{
		return $this->period;
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
			if (count($this->schedules) === 1)
			{
				foreach ($this->schedules as $schedule)
				{
					switch ($schedule->getReportPeriod())
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
					break;
				}
			}

			$presets = [
				'timeman_worktime_grid_filter_period' => [
					'name' => Loc::getMessage('TM_REPORT_FILTER_PRESET_REPORT_PERIOD'),
					'default' => true,
					'fields' => $fields,
				],
			];
			$this->filter = [
				'ID' => $filterId,
				'FIELDS' => [
					[
						'id' => 'REPORT_PERIOD',
						'name' => Loc::getMessage('TM_REPORT_FILTER_PRESET_REPORT_PERIOD'),
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
					[
						'id' => 'USERS',
						'name' => Loc::getMessage('TM_GRID_HEADER_USERS_LABEL'),
						'type' => 'dest_selector',
						'default' => true,
						'params' => [
							'apiVersion' => '3',
							'context' => $this->id . '_USERS',
							'multiple' => 'Y',
							'contextCode' => 'U',
							'enableAll' => 'N',
							'enableSonetgroups' => 'N',
							'allowEmailInvitation' => 'N',
							'departmentSelectDisable' => 'Y',
						],
					],
					[
						'id' => 'DEPARTMENTS',
						'name' => Loc::getMessage('TM_GRID_FILTER_DEPARTMENTS_LABEL'),
						'type' => 'dest_selector',
						'default' => true,
						'params' => [
							'apiVersion' => '3',
							'context' => $this->id . '_DEPARTMENTS',
							'multiple' => 'N',
							'contextCode' => 'U',
							'enableAll' => 'N',
							'departmentSelectDisable' => 'N',
							'enableSonetgroups' => 'N',
							'enableUsers' => 'N',
							'allowEmailInvitation' => 'N',
						],
					],
				],
				'PRESETS' => $presets,
			];
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
		foreach ($this->periodDates as $date)
		{
			$res[$date] = $this->getPeriod()[$date];
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
			if ($this->options['ENABLE_STATS_COLUMNS'] && $this->options['SHOW_STATS_COLUMNS'])
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
			$helper = new \Bitrix\Timeman\Helper\DateTimeHelper();
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
			$userNow = TimeHelper::getInstance()->getUserDateTimeNow($this->currentUserId);
			$userNow = $userNow ? $userNow->format('Y-m-d') : '';
			foreach ($this->periodDates as $date)
			{
				$item = [
					'id' => $date,
					'name' => $helper->formatDate($headerDateFormat, $this->getPeriod()[$date]),
					'default' => true,
				];
				if ($this->getPeriod()[$date]->format('Y-m-d') == $userNow)
				{
					$item['class'] .= ' js-tm-header-today timeman-grid-column-header-today';
				}
				$this->headers[] = $item;
			}
		}

		return $this->headers;
	}

	public function getWorktimeStatistics(&$gridData)
	{
		$statsResult = [];
		foreach ($gridData as $userId => $rowData)
		{
			$statsResult[$userId]['TOTAL_NOT_APPROVED_WORKDAYS'] = 0;
			$statsResult[$userId]['TOTAL_VIOLATIONS'] = [
				'PERSONAL' => 0,
				'COMMON' => 0,
			];
			$statsResult[$userId]['TOTAL_WORKDAYS'] = 0;

			foreach ($rowData as $key => $cellData)
			{
				foreach ($cellData as $recordData)
				{
					if (empty($recordData['WORKTIME_RECORD']))
					{
						continue;
					}
					$record = $recordData['WORKTIME_RECORD'];

					$dayIsEnded = $record['RECORDED_STOP_TIMESTAMP'] > 0;
					$dayIsApproved = $record['IS_APPROVED'];

					if (!$dayIsApproved || (isset($record['EXPIRED']) && $record['EXPIRED'] === true))
					{
						$statsResult[$userId]['TOTAL_NOT_APPROVED_WORKDAYS']++;
					}
					else
					{
						$statsResult[$userId]['TOTAL_WORKDAYS']++;
						$statsResult[$userId]['TOTAL_WORKED_SECONDS'] += $record['CALCULATED_DURATION'];
					}
					if ($dayIsEnded && $dayIsApproved)
					{
						$countedPersonalViolations = false;
						$countedCommonViolations = false;
						if (!empty($record['WARNINGS']) && $dayIsApproved)
						{
							foreach ($record['WARNINGS'] as $warning)
							{
								if ($warning['type'] === 'personal' && !$countedPersonalViolations)
								{
									$countedPersonalViolations = true;
									$statsResult[$userId]['TOTAL_VIOLATIONS']['PERSONAL']++;
								}
								if ($warning['type'] === 'common' && !$countedCommonViolations)
								{
									$countedCommonViolations = true;
									$statsResult[$userId]['TOTAL_VIOLATIONS']['COMMON']++;
								}
								if ($countedPersonalViolations && $countedCommonViolations)
								{
									break;
								}
							}
						}
					}
				}
			}
		}
		return $statsResult;
	}

	/**
	 * @return array
	 */
	public function getRowsData(&$users, &$gridData, $userViolationRulesMap, $options = [])
	{
		$this->userViolationRulesMap = $userViolationRulesMap;
		$result = [];
		$drawnAbsences = [];
		$midnight = TimeHelper::getInstance()->getUserDateTimeNow($this->currentUserId)->getTimestamp() - 1;
		$absenceData = [];
		if ($this->options['SHOW_USER_ABSENCES'])
		{
			$absenceData = $this->prepareAbsenceData(array_keys($users));
		}
		foreach ($users as $user)
		{
			foreach ($this->periodDates as $periodDate)
			{
				$result[$user['ID']][$periodDate] = [];
				$userCellAbsence = [];
				if ($absenceData[$user['ID']])
				{
					foreach ($absenceData[$user['ID']] as $absIndex => $absenceItem)
					{
						$absItem = [];
						if ($absenceItem['tm_absStartFormatted'] == $periodDate && $absenceItem['tm_absEndFormatted'] == $periodDate)
						{
							$absItem['ABSENCE_PART'] = 'full';
						}
						elseif ($absenceItem['tm_absStartFormatted'] == $periodDate)
						{
							$absItem['ABSENCE_PART'] = 'start';
						}
						elseif ($absenceItem['tm_absEndFormatted'] == $periodDate)
						{
							$absItem['ABSENCE_PART'] = 'end';
						}
						else
						{
							if ($absenceItem['tm_absStartDateTime'] && $absenceItem['tm_absEndDateTime']
								&& $absenceItem['tm_absStartDateTime']->getTimestamp() < $this->getPeriod()[$periodDate]->getTimestamp()
								&& $absenceItem['tm_absEndDateTime']->getTimestamp() > $this->getPeriod()[$periodDate]->getTimestamp())
							{
								$absItem['ABSENCE_PART'] = 'middle';
							}
						}
						if (!empty($absItem['ABSENCE_PART']))
						{
							$absItem['ABSENCE_HINT'] = $absenceItem['NAME'] . ' (' . $absenceItem['DATE_ACTIVE_FROM'] . ' - ' . $absenceItem['DATE_ACTIVE_TO'] . ')';
							if (!isset($drawnAbsences[$absenceItem['ID']]))
							{
								$absItem['ABSENCE_TITLE'] = $absItem['ABSENCE_HINT'];
							}
							$absItem['ABSENCE'] = true;
							$userCellAbsence = $absItem;
							$drawnAbsences[$absenceItem['ID']] = true;
						}
					}
				}

				$drawnShiftIds = [];
				# there are worked time records or shift plans
				if (!empty($gridData[$user['ID']][$periodDate]))
				{
					foreach ($gridData[$user['ID']][$periodDate] as $recordPlanData)
					{
						$shift = null;
						$schedule = null;
						$record = $recordPlanData['record'];
						$plan = $recordPlanData['plan'];
						if ($plan)
						{
							$drawnShiftIds[] = (int)$plan['SHIFT_ID'];
						}
						if ($record)
						{
							if (!array_key_exists('CALCULATED_DURATION', $record))
							{
								$record['CALCULATED_DURATION'] = WorktimeRecord::wakeUp($record)->calculateCurrentDuration();
							}
							$shift = $this->getShiftById($record['SHIFT_ID']);
							$schedule = $this->getScheduleById($record['SCHEDULE_ID']);
							if (WorktimeRecord::isRecordExpired($record, $schedule, $shift))
							{
								$record['EXPIRED'] = true;
							}
							if ($schedule)
							{
								$violations = $this->buildViolations($record, $absenceData);
								foreach ($violations as $violation)
								{
									$key = 'WARNINGS';
									if (in_array($violation->type, [
										WorktimeViolation::TYPE_EDITED_BREAK_LENGTH,
										WorktimeViolation::TYPE_EDITED_START,
										WorktimeViolation::TYPE_EDITED_ENDING,
									], true))
									{
										$key = 'VIOLATIONS';
									}
									$postfix = $violation->violationRules->isForAllUsers() ? 'common' : 'personal';
									$record[$key][$violation->type . $postfix] = ['text' => $this->buildViolationText($violation, $user['PERSONAL_GENDER']), 'type' => $postfix];
								}
							}

							$record['RECORD_LINK'] = $this->urlManager->getUriTo('recordReport', ['RECORD_ID' => $record['ID']]);
						}
						if (!$shift)
						{
							$shift = $this->getShiftById($plan['SHIFT_ID']);
						}
						if (!$schedule)
						{
							$schedule = $this->getScheduleByShiftId($plan['SHIFT_ID']);
						}
						$record['IS_APPROVED'] = WorktimeRecord::isRecordApproved($record);
						$resultCellData = [
							'IS_SHIFTED_SCHEDULE' => Schedule::isScheduleShifted($schedule),
							'WORKTIME_RECORD' => $record,
							'SHIFT_PLAN' => $plan,
							'WORK_SHIFT' => !empty($shift) ? $shift->collectValues() : [],
							'USER_ID' => $user['ID'],
							'DRAWING_DATE' => $this->getPeriod()[$periodDate],
						];
						if (!empty($userCellAbsence))
						{
							$resultCellData = array_merge($resultCellData, $userCellAbsence);
							$userCellAbsence = [];
						}
						$result[$user['ID']][$periodDate][] = $resultCellData;
					}
				}
				if (!empty($userCellAbsence))
				{
					$result[$user['ID']][$periodDate][] = $userCellAbsence;
				}
				if (!$user['SCHEDULE_ID'])
				{
					continue;
				}
				# no worked time records or shift plans
				if ($options['SHOW_ADD_SHIFT_PLAN_BTN'] &&
					Schedule::isScheduleShifted($this->getScheduleById($user['SCHEDULE_ID']))
					&& $this->getPeriod()[$periodDate]->format('U') > $midnight)
				{
					foreach ($this->getScheduleById($user['SCHEDULE_ID'])->obtainShifts() as $shift)
					{
						if (in_array((int)$shift['ID'], $drawnShiftIds, true))
						{
							continue;
						}
						$result[$user['ID']][$periodDate][] = [
							'IS_SHIFTED_SCHEDULE' => Schedule::isScheduleShifted($this->getScheduleById($user['SCHEDULE_ID'])),
							'SHOW_ADD_SHIFT_PLAN_BTN' => true,
							'WORK_SHIFT' => $shift->collectValues(),
							'USER_ID' => $user['ID'],
							'DRAWING_DATE' => $this->getPeriod()[$periodDate],
						];
					}
				}
			}
		}
		$this->userViolationRulesMap = [];
		return $result;
	}

	private function getScheduleByShiftId($shiftId = null)
	{
		foreach ($this->schedules as $schedule)
		{
			if ($shift = $schedule->obtainShiftByPrimary($shiftId))
			{
				return $schedule;
			}
		}
		return null;
	}

	private function getScheduleById($id = null)
	{
		foreach ($this->schedules as $schedule)
		{
			if ((int)$schedule->getId() === (int)$id)
			{
				return $schedule;
			}
		}
		return null;
	}

	public function getDepartmentId()
	{
		if ($this->getFilter()['DATA'] && $this->getFilter()['DATA']['DEPARTMENTS'])
		{
			if (preg_match('#DR[0-9]+#', $this->getFilter()['DATA']['DEPARTMENTS']) === 1)
			{
				return (int)substr($this->getFilter()['DATA']['DEPARTMENTS'], 2);
			}
		}
		return null;
	}

	public function isDepartmentFilterApplied()
	{
		return $this->getFilter()['DATA'] && $this->getFilter()['DATA']['DEPARTMENTS'];
	}

	public function isUserFilterApplied()
	{
		return $this->getFilter()['DATA'] && $this->getFilter()['DATA']['USERS'];
	}

	public function getUserIds()
	{
		if ($this->getFilter()['DATA'] && $this->getFilter()['DATA']['USERS'])
		{
			$userIds = [];
			if (!is_array($this->getFilter()['DATA']['USERS']))
			{
				if (preg_match('#U[0-9]+#', $this->getFilter()['DATA']['USERS']) === 1)
				{
					return [substr($this->getFilter()['DATA']['USERS'], 1)];
				}
			}
			foreach ($this->getFilter()['DATA']['USERS'] as $userId)
			{
				if (preg_match('#U[0-9]+#', $userId) === 1)
				{
					$userIds[] = substr($userId, 1);
				}
			}
			return array_map('intval', $userIds);
		}
		return [];
	}

	public function isShowUsersWithRecordsOnly()
	{
		return $this->getFilter() &&
			   $this->getFilter()['DATA'] &&
			   $this->getFilter()['DATA']['SHOW_ALL'] === 'N';
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
		if ($this->getFilter() &&
			$this->getFilter()['DATA'] &&
			$this->getFilter()['DATA']['IS_REPORT_APPROVED'])
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
	 * @param Timeman\Service\Worktime\Violation\WorktimeViolation $violation
	 * @return string
	 */
	public function buildViolationText($violation, $userGender)
	{
		$text = '';
		$formattedTime = $this->timeHelper->convertSecondsToHoursMinutesLocal($violation->violatedSeconds, false);
		if (strncmp('-', $formattedTime, 1) === 0)
		{
			$formattedTime = substr($formattedTime, 1);
		}
		$editedText = Loc::getMessage('TM_WORKTIME_STATS_EDITED_MALE', ['#TIME#' => $formattedTime]);
		if ($userGender === 'F')
		{
			$editedText = Loc::getMessage('TM_WORKTIME_STATS_EDITED_FEMALE', ['#TIME#' => $formattedTime]);
		}
		switch ($violation->type)
		{
			case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
				$text = Loc::getMessage('TM_WORKTIME_STATS_BREAK_TITLE');
				$formattedTime = $editedText;
				break;
			case WorktimeViolation::TYPE_LATE_START:
			case WorktimeViolation::TYPE_SHIFT_LATE_START:
				$text = Loc::getMessage('TM_WORKTIME_STATS_START_TITLE');
				$extraText = Loc::getMessage('TM_WORKTIME_STATS_START_LATE_MALE', ['#TIME#' => $formattedTime]);
				if ($userGender === 'F')
				{
					$extraText = Loc::getMessage('TM_WORKTIME_STATS_START_LATE_FEMALE', ['#TIME#' => $formattedTime]);
				}
				$formattedTime = $extraText;
				break;
			case WorktimeViolation::TYPE_EDITED_START:
				$text = Loc::getMessage('TM_WORKTIME_STATS_START_TITLE');
				$formattedTime = $editedText;
				break;
			case WorktimeViolation::TYPE_EARLY_START:
				$text = Loc::getMessage('TM_WORKTIME_STATS_START_TITLE');
				$formattedTime = Loc::getMessage('TM_WORKTIME_STATS_EARLY', ['#TIME#' => $formattedTime]);
				break;
			case WorktimeViolation::TYPE_MIN_DAY_DURATION:
				$text = Loc::getMessage('TM_WORKTIME_STATS_DURATION_VIOLATION');
				$extraText = Loc::getMessage('TM_WORKTIME_STATS_DURATION_MALE', ['#TIME#' => $formattedTime]);
				if ($userGender === 'F')
				{
					$extraText = Loc::getMessage('TM_WORKTIME_STATS_DURATION_FEMALE', ['#TIME#' => $formattedTime]);
				}
				$formattedTime = $extraText;
				break;
			case WorktimeViolation::TYPE_EARLY_ENDING:
				$text = Loc::getMessage('TM_WORKTIME_STATS_STOP_TITLE');
				$formattedTime = Loc::getMessage('TM_WORKTIME_STATS_EARLY', ['#TIME#' => $formattedTime]);
				break;
			case WorktimeViolation::TYPE_LATE_ENDING:
				$text = Loc::getMessage('TM_WORKTIME_STATS_STOP_TITLE');
				$formattedTime = Loc::getMessage('TM_WORKTIME_STATS_LATE', ['#TIME#' => $formattedTime]);
				break;
			case WorktimeViolation::TYPE_EDITED_ENDING:
				$text = Loc::getMessage('TM_WORKTIME_STATS_STOP_TITLE');
				$formattedTime = $editedText;
				break;
			default:
				break;
		}

		return $text . ': '
			   . $this->timeHelper->convertSecondsToHoursMinutesLocal($violation->recordedSeconds)
			   . "<span class=\"tm-grid-worktime-popup-violation\">&nbsp;("
			   . $formattedTime
			   . ')</span>';
	}

	/**
	 * @param array $record
	 */
	private function buildViolations(&$record, &$absenceData)
	{
		if (!$this->isTimemanEnabled($record['USER_ID']))
		{
			return [];
		}
		$personal = $this->violationManager
			->buildViolations((new WorktimeViolationParams())
				->setCurrentUserId($this->currentUserId)
				->setShift($this->getShiftById($record['SHIFT_ID']))
				->setSchedule($schedule = $this->getScheduleById($record['SCHEDULE_ID']))
				->setViolationRules($this->getViolationRulesByUser($record, $schedule))
				->setRecord($record)
				->setAbsenceData($absenceData));
		$common = $this->violationManager
			->buildViolations((new WorktimeViolationParams())
				->setCurrentUserId($this->currentUserId)
				->setShift($this->getShiftById($record['SHIFT_ID']))
				->setSchedule($schedule = $this->getScheduleById($record['SCHEDULE_ID']))
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
	private function getViolationRulesByUser($record, $schedule)
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

	private function getShiftById($shiftId)
	{
		foreach ($this->schedules as $schedule)
		{
			if ($shift = $schedule->obtainShiftByPrimary($shiftId))
			{
				return $shift;
			}
		}
		return null;
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
				if (strtolower($dateTimeFrom->format('l')) !== $this->getFirstWeekDayWord())
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
					$this->currentUserId
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
						$this->currentUserId
					),
					$this->currentUserId
				);
				$resultToDate = TimeHelper::getInstance()->createUserDateTimeFromFormat(
					'U',
					TimeHelper::getInstance()->getTimestampByUserDate(
						$endSourceDate,
						$this->currentUserId
					),
					$this->currentUserId
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

	private function prepareAbsenceData($userIds)
	{
		return DependencyManager::getInstance()
			->getAbsenceRepository()
			->findAbsences(
				convertTimeStamp($this->getPeriod()[reset($this->periodDates)]->format('U'), 'FULL'),
				convertTimeStamp($this->getPeriod()[end($this->periodDates)]->format('U'), 'FULL'),
				$this->currentUserId,
				$userIds
			);
	}

	public function isFilterByApprovedApplied()
	{
		return in_array($this->getFilterByApproved(), ['Y', 'N'], true);
	}

	private function isTimemanEnabled($userId)
	{
		if ($this->timemanEnabledSettings[$userId] === null)
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
}