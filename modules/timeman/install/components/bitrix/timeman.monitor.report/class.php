<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Grid;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\NumberType;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Timeman\Monitor\Constant\Group;
use Bitrix\Timeman\Monitor\Report\TimelineReport;
use Bitrix\Timeman\Monitor\Report\WorkingHoursByDateReport;
use Bitrix\Timeman\Monitor\Report\WorkingHoursReport;
use Bitrix\Timeman\Monitor\Security\Permissions;
use Bitrix\Timeman\Monitor\Utils\Department;
use Bitrix\Timeman\Monitor\Utils\User;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Type\DateTime;

class TimemanMonitorReportComponent extends CBitrixComponent implements Controllerable
{
	protected $gridId = 'timeman_monitor_report_grid';
	protected $filterId = 'timeman_monitor_report_filter';
	protected $pageNavigation;
	protected $gridOptions;

	protected const ONE_HOUR = 3600;

	private const BASE_YEAR = 2021;
	private const BASE_MONTH = 1;
	private const BASE_DAY = 1;

	public function executeComponent(): bool
	{
		$this->init();

		$report = $this->createReport();
		if ($report)
		{
			$this->prepareReport($report);
		}
		else
		{
			$this->gridOptions->resetExpandedRows();
			$this->arResult['ROWS'] = [];
		}

		$this->includeComponentTemplate();

		return true;
	}

	protected function init(): void
	{
		$this->includeModules();

		$this->gridOptions = new Grid\Options($this->gridId);
		$this->pageNavigation = new PageNavigation('page');

		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['FILTER_ID'] = $this->filterId;
		$this->arResult['FILTER'] = $this->getFilterDefinition();
		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresets();
		$this->arResult['HEADERS'] = [
			[
				'id' => 'COLUMN_1',
				'name' => Loc::getMessage('TIMEMAN_PWT_REPORT_GRID_HEADER_EMPLOYEE'),
				'default' => true,
				'editable' => false,
				'shift' => true
			],
			[
				'id' => 'COLUMN_2',
				'name' => Loc::getMessage('TIMEMAN_PWT_REPORT_GRID_HEADER_WORKING_HOURS'),
				'default' => true,
				'editable' => false,
				'sort' => 'WORKING_HOURS'
			],
		];
	}

	protected function includeModules()
	{
		if (!Loader::includeModule('timeman'))
		{
			throw new SystemException('Module "timeman" is not installed.');
		}
	}

	protected function createReport(): ?WorkingHoursReport
	{
		$filter = $this->getFilter();

		if (!$filter)
		{
			return null;
		}

		$order =
			$this->gridOptions->GetSorting([
				'sort' => [
					'WORKING_HOURS' => 'DESC'
				]
			])['sort']
		;

		$navParams = $this->gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$this->pageNavigation
			->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri()
		;

		$limit = $this->pageNavigation->getLimit() + 1;
		$offset = $this->pageNavigation->getOffset();

		return new WorkingHoursReport($filter, $order, $limit, $offset);
	}

	protected function prepareReport(WorkingHoursReport $report): bool
	{
		$reportData = $report->getData();

		$culture = Context::getCurrent()->getCulture();
		$dateFormat = $culture->getDayOfWeekMonthFormat() . ' Y';

		$rows = [];
		foreach ($reportData as $index => $row)
		{
			if ($index === $this->pageNavigation->getLimit())
			{
				break;
			}

			$rows[] = [
				'id' => 'user-' . $row['USER_ID'],
				'columns' => [
					'EMPLOYEE_ID' => $row['USER_ID'],
					'EMPLOYEE_NAME' => $row['USER_NAME'],
					'EMPLOYEE_ICON' => $row['USER_ICON'],
					'EMPLOYEE_LINK' => $row['USER_LINK'],
					'WORKING_HOURS' => $row['WORKING_HOURS'],
				],
				'actions' => [],
				'has_child' => true,
				'parent_id' => 0,
			];
		}

		$expandedRows = $this->gridOptions->getExpandedRows();
		$updatedExpandedRows = [];
		$expandedUserIds = [];

		if (is_array($expandedRows))
		{
			foreach ($expandedRows as $expandedRowId)
			{
				if (in_array($expandedRowId, array_column($rows, 'id'), true))
				{
					$expandedUserIds[] = (int)explode('user-', $expandedRowId)[1];
					$updatedExpandedRows[] = $expandedRowId;
				}
			}
		}

		if ($expandedUserIds)
		{
			if (array_diff($expandedRows, $updatedExpandedRows))
			{
				$this->gridOptions->setExpandedRows($updatedExpandedRows);
			}

			$filter = $this->getTimelineFilter($expandedUserIds);

			$timelineReport = new TimelineReport($filter);
			$timelineReportData = $timelineReport->getData();

			$timelineLegendReport = new WorkingHoursByDateReport($filter);
			$timelineLegendReportData = $timelineLegendReport->getData();

			$timeLimits = $this->calculateTimeLimits($timelineReportData);

			foreach ($timelineReportData as $userId => $dates)
			{
				foreach ($dates as $date => $desktops)
				{
					$desktopCode = array_key_first($desktops);
					$chartData = $this->prepareChartData([
						'data' => array_shift($desktops),
						'minStartTime' => $timeLimits['minStartTime'],
						'maxFinishTime' => $timeLimits['maxFinishTime'],
					]);

					$rows[] = [
						'id' => 'user_' . $userId . '-report_' . $date,
						'columns' => [
							'DATE' => FormatDate($dateFormat, (new Date($date, 'Y-m-d'))),
							'WORK_TIME' => $timelineLegendReportData[$userId][$date][$desktopCode]['formatted'],
							'CHART_DATA' => $chartData,
							'USER_ID' => $userId,
							'DATE_LOG' => $date,
						],
						'actions' => [],
						'parent_id' => 'user-' . $userId,
						'has_child' => false,
						'pwt_custom' => [
							'is_placeholder' => true,
						],
					];
				}
			}
		}

		$this->arResult['ROWS'] = $rows;

		$this->pageNavigation->setRecordCount($this->pageNavigation->getOffset() + count($reportData));
		$this->arResult['NAV_OBJECT'] = $this->pageNavigation;

		return true;
	}

	protected function getFilterDefinition(): array
	{
		$result = [];

		$result['TIME_PERIOD'] = [
			'id' => 'TIME_PERIOD',
			'name' => Loc::getMessage('TIMEMAN_PWT_REPORT_FILTER_PERIOD'),
			'type' => 'date',
			'exclude' => [
				DateType::NONE,
				DateType::TOMORROW,
				DateType::NEXT_DAYS,
				DateType::NEXT_WEEK,
				DateType::NEXT_MONTH,
			],
			'default' => true,
		];

		$result['EMPLOYEE'] = [
			'id' => 'EMPLOYEE',
			'name' => Loc::getMessage('TIMEMAN_PWT_REPORT_FILTER_EMPLOYEE'),
			'type' => 'dest_selector',
			'params' => array (
				'apiVersion' => '3',
				'context' => 'TIMEMAN_PWT_REPORT_FILTER_EMPLOYEE',
				'multiple' => 'Y',
				'contextCode' => 'U',
				'enableAll' => 'N',
				'enableSonetgroups' => 'N',
				'allowEmailInvitation' => 'N',
				'allowSearchEmailUsers' => 'N',
				'departmentSelectDisable' => 'N',
			),
			'default' => true,
		];

		$result['WORKING_HOURS'] = [
			'id' => 'WORKING_HOURS',
			'name' => Loc::getMessage('TIMEMAN_PWT_REPORT_FILTER_WORKING_HOURS'),
			'type' => 'number',
			'default' => true,
		];

		return $result;
	}

	protected function getFilterPresets(): array
	{
		$presets['filter_my_report_for_current_month'] = [
			'name' => Loc::getMessage('TIMEMAN_PWT_REPORT_FILTER_PRESET_CURRENT_MONTH'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'EMPLOYEE_label' => User::getCurrentUserName(),
				'EMPLOYEE' => 'U' . User::getCurrentUserId(),
				'WORKING_HOURS_numsel' => Filter\NumberType::LESS,
			],
			'default' => true,
		];

		return $presets;
	}

	protected function getFilter(): ?array
	{
		$filterOptions = new Filter\Options($this->filterId);
		$rawFilter = $filterOptions->getFilter($this->getFilterDefinition());

		$filter = [];

		if(isset($rawFilter['EMPLOYEE']) && is_array($rawFilter['EMPLOYEE']))
		{
			$requestedUserIds = [];
			$filterDepartmentIds = [];

			foreach ($rawFilter['EMPLOYEE'] as $index => $id)
			{
				if(mb_substr($id, 0, 1) === 'U')
				{
					$requestedUserIds[] = (int)mb_substr($id, 1);
				}
				elseif (mb_substr($id, 0, 2) === 'DR')
				{
					$filterDepartmentIds[] = (int)mb_substr($id, 2);
				}
			}

			if ($filterDepartmentIds)
			{
				$requestedUserIdsByDepartments = Department::getDepartmentsEmployees($filterDepartmentIds, true);

				$requestedUserIds = array_unique(array_merge($requestedUserIds, $requestedUserIdsByDepartments));
			}

			$permissions = Permissions::createForCurrentUser();
			$availableUserIds = array_intersect($permissions->getAvailableUserIds(), $requestedUserIds);

			if ($availableUserIds)
			{
				$filter['@USER_ID']	= $availableUserIds;
			}
			else
			{
				return null;
			}
		}
		else
		{
			return null;
		}

		if (isset($rawFilter['TIME_PERIOD_from']) && $rawFilter['TIME_PERIOD_from'] !== '')
		{
			$filter['>=DATE_LOG'] = new Date($rawFilter['TIME_PERIOD_from']);
		}

		if (isset($rawFilter['TIME_PERIOD_to']) && $rawFilter['TIME_PERIOD_to'] !== '')
		{
			$filter['<=DATE_LOG'] = new Date($rawFilter['TIME_PERIOD_to']);
		}

		if (isset($rawFilter['WORKING_HOURS_from']) && $rawFilter['WORKING_HOURS_from'] !== '')
		{
			$operation = $rawFilter['WORKING_HOURS_numsel'] === NumberType::MORE ? '>' : '>=';
			$filter[$operation . 'WORKING_HOURS'] = (int)$rawFilter['WORKING_HOURS_from'];
		}

		if (isset($rawFilter['WORKING_HOURS_to']) && $rawFilter['WORKING_HOURS_to'] !== '')
		{
			$operation = $rawFilter['WORKING_HOURS_numsel'] === NumberType::LESS ? '<' : '<=';
			$filter[$operation . 'WORKING_HOURS'] = (int)$rawFilter['WORKING_HOURS_to'];
		}

		return $filter;
	}

	protected function getTimelineFilter(array $userIds): ?array
	{
		$filterOptions = new Filter\Options($this->filterId);
		$rawFilter = $filterOptions->getFilter($this->getFilterDefinition());

		$filter = [];

		$permissions = Permissions::createForCurrentUser();
		$availableUserIds = array_intersect($permissions->getAvailableUserIds(), $userIds);

		if ($availableUserIds)
		{
			$filter['@USER_ID']	= $availableUserIds;
		}
		else
		{
			return null;
		}

		if (isset($rawFilter['TIME_PERIOD_from']) && $rawFilter['TIME_PERIOD_from'] !== '')
		{
			$filter['>=DATE_LOG'] = new Date($rawFilter['TIME_PERIOD_from']);
		}

		if (isset($rawFilter['TIME_PERIOD_to']) && $rawFilter['TIME_PERIOD_to'] !== '')
		{
			$filter['<=DATE_LOG'] = new Date($rawFilter['TIME_PERIOD_to']);
		}

		return $filter;
	}

	private function calculateTimeLimits($timelineReportData): array
	{
		$allIntervals = [];
		foreach ($timelineReportData as $reportsByDate)
		{
			foreach ($reportsByDate as $reportByDesktopCode)
			{
				foreach ($reportByDesktopCode as $intervals)
				{
					foreach ($intervals as $interval)
					{
						if ($interval['type'] === Group::INACTIVE)
						{
							continue;
						}

						$timeStart = new \DateTime($interval['start']);
						$timeFinish = new \DateTime($interval['finish']);

						$timestampStart =
							$timeStart
								->setDate(self::BASE_YEAR, self::BASE_MONTH,self::BASE_DAY)
								->getTimestamp()
						;

						$timestampFinish =
							$timeFinish
								->setDate(self::BASE_YEAR, self::BASE_MONTH,self::BASE_DAY)
								->getTimestamp()
						;

						$processedInterval['start'] = $timestampStart - ($timeStart->format('i') * 60) - self::ONE_HOUR;
						$processedInterval['finish'] = $timestampFinish - ($timeFinish->format('i') * 60) + (2 * self::ONE_HOUR);

						$allIntervals[] = $processedInterval;
					}
				}
			}
		}

		$minStartTime = min(array_column($allIntervals, 'start'));
		$maxFinishTime = max(array_column($allIntervals, 'finish'));

		return [
			'minStartTime' => $minStartTime,
			'maxFinishTime' => $maxFinishTime
		];
	}

	protected function prepareChartData(array $timelineReport): array
	{
		if (!$timelineReport['data'])
		{
			return [];
		}

		$dateFormat = Context::getCurrent()->getCulture()->getShortTimeFormat();

		$intervals = [];
		foreach ($timelineReport['data'] as $index => $interval)
		{
			if ($index === 0)
			{
				$interval['start'] = $timelineReport['minStartTime'];
				$interval['finish'] =
					(new \DateTime($interval['finish']))
						->setDate(self::BASE_YEAR, self::BASE_MONTH,self::BASE_DAY)
						->getTimestamp()
				;
			}
			elseif ($index === count($timelineReport['data']) - 1)
			{
				$interval['start'] =
					(new \DateTime($interval['start']))
						->setDate(self::BASE_YEAR, self::BASE_MONTH,self::BASE_DAY)
						->getTimestamp()
				;
				$interval['finish'] = $timelineReport['maxFinishTime'];
			}
			else
			{
				$interval['start'] =
					(new \DateTime($interval['start']))
						->setDate(self::BASE_YEAR, self::BASE_MONTH,self::BASE_DAY)
						->getTimestamp()
				;
				$interval['finish'] =
					(new \DateTime($interval['finish']))
						->setDate(self::BASE_YEAR, self::BASE_MONTH,self::BASE_DAY)
						->getTimestamp()
				;
			}

			$interval['startFormatted'] = FormatDate($dateFormat, $interval['start']);
			$interval['finishFormatted'] = FormatDate($dateFormat, $interval['finish']);

			$interval['time'] = $interval['finish'] - $interval['start'];

			$intervals[] = $interval;
		}

		$totalTime = array_sum(array_column($intervals, 'time'));

		$lastStartMarkerTime = null;

		foreach ($intervals as $index => $interval)
		{
			if ($index === 0)
			{
				$intervals[$index]['showStartMarker'] = true;
				$lastStartMarkerTime = $interval['start'];
			}
			else if ($interval['start'] - $lastStartMarkerTime >= self::ONE_HOUR)
			{
				$intervals[$index]['showStartMarker'] = true;
				$lastStartMarkerTime = $interval['start'];
			}

			$intervals[$index]['showFinishMarker'] = (
				$index === count($intervals) - 1
			);

			$intervals[$index]['size'] = 100 / ($totalTime / $intervals[$index]['time']);
		}

		$intervals[0]['isFirst'] = true;
		$intervals[count($intervals) - 1]['isLast'] = true;

		$timeFinish = DateTime::createFromTimestamp($intervals[count($intervals) - 1]['finish']);
		$hourFinish = (int)$timeFinish->format('H');
		$minFinish = (int)$timeFinish->format('i');
		if (
			(
				$hourFinish === 23
				&& $minFinish === 59
			)
			|| (
				$hourFinish === 0
				&& $minFinish === 0
			)
		)
		{
			$intervals[count($intervals) - 1]['finishFormatted'] = '24:00';
		}

		//to avoid collisions with the start marker of the last interval, which is always displayed
		$intervalsLength = count($intervals);
		if ($intervalsLength > 3)
		{
			$intervals[$intervalsLength - 1]['showStartMarker'] = true;

			for ($i = $intervalsLength - 2; $i > 0; $i--)
			{
				if (
					$intervals[$i]['showStartMarker']
					&& $intervals[$intervalsLength - 1]['start'] - $intervals[$i]['start'] < self::ONE_HOUR
				)
				{
					$intervals[$i]['showStartMarker'] = false;
					break;
				}
			}
		}

		//to avoid collisions between markers of the last interval
		if ($intervals[$intervalsLength - 1]['finish'] - $intervals[$intervalsLength - 1]['start'] <= self::ONE_HOUR)
		{
			$intervals[$intervalsLength - 1]['showStartMarker'] = false;
		}

		return $intervals;
	}

	public function getRowsCountAction(): array
	{
		$this->init();

		$rowsCount = 0;

		$report = $this->createReport();
		if ($report)
		{
			$rowsCount = $report->getTotalCount();
		}

		return [
			'rowsCount' => $rowsCount
		];
	}

	public function configureActions(): array
	{
		return [];
	}
}
