<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\CallActivity;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleBiGroupedData;
use Bitrix\Voximplant\ConfigTable;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Integration\Report\Helper\TimeHelper;
use Bitrix\Voximplant\Model\NumberTable;
use Bitrix\Voximplant\SipTable;
use Bitrix\Voximplant\StatisticTable;
use CVoxImplantMain;

/**
 * Class CallActivity
 * @package Bitrix\Voximplant\Integration\Report\Handler\CallActivity
 */
class CallActivityGraph extends CallActivity implements IReportMultipleBiGroupedData
{
	protected $reportFilterKeysForSlider = [
		'TIME_PERIOD_from',
		'TIME_PERIOD_to',
		'PORTAL_NUMBER',
		'PHONE_NUMBER',
		'PORTAL_USER_ID'
	];

	public function getMultipleBiGroupedData()
	{
		$calculatedData = $this->getCalculatedData();

		$result = [];
		foreach ($calculatedData['report'] as $row)
		{
			$incoming = (int)$row['CALL_INCOMING'];
			$missed = (int)$row['CALL_MISSED'];

			if (!$incoming && !$missed)
			{
				continue;
			}

			$hour = $row['HOUR'];
			$dayOfWeek = $row['DAY_OF_WEEK'];

			$result['items'][] = [
				'firstGroupId' => $hour,
				'secondGroupId' => $dayOfWeek,
				'incoming' => $incoming,
				'missed' => $missed,
				'url' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
					'INCOMING' => CVoxImplantMain::CALL_INCOMING,
					'STATUS' => self::CALL_STATUS_FAILURE,
					'HOUR' => $hour,
					'DAY_OF_WEEK' => $dayOfWeek,
				]),
			];
		}

		$result['workingHours'] = $this->getWorkingHoursForGraph();

		return $result;
	}

	public function getMultipleBiGroupedDemoData()
	{

	}

	/**
	 * @return array|mixed
	 */
	public function prepare()
	{
		if (!$this->isCurrentUserHasAccess())
		{
			return [];
		}

		$filterParameters = $this->getFilterParameters();

		$startDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD_from']);
		$finishDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD_to']);

		$report = $this->getQueryForReport($startDate, $finishDate, $filterParameters)->exec()->fetchAll();

		return [
			'report' => $report
		];
	}

	/**
	 * Creates a query to select data based on a filter.
	 *
	 * @param $startDate
	 * @param $finishDate
	 * @param $filterParameters
	 *
	 * @return Query
	 */
	public function getQueryForReport($startDate, $finishDate, $filterParameters): Query
	{
		$query = StatisticTable::query();

		$this->addDateWithGrouping($query);
		$query->addSelect('DAY_OF_WEEK');
		$query->addSelect('HOUR');

		$helper = Application::getConnection()->getSqlHelper();
		$dateWithShift = $this->getDateExpressionWithTimeShiftForQuery();
		$query->registerRuntimeField(new ExpressionField(
			'DAY_OF_WEEK',
			$helper->formatDate('%W', $dateWithShift), // % is needed for escaping
			['CALL_START_DATE']
		));

		$query->registerRuntimeField(new ExpressionField(
			'HOUR',
			"extract(hour from $dateWithShift)",
			['CALL_START_DATE']
		));

		$this->addCallTypeField($query, CallType::INCOMING, 'CALL_INCOMING');
		$this->addCallTypeField($query, CallType::MISSED, 'CALL_MISSED');

		$this->addToQueryFilterCase($query, $filterParameters);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);

		return $query;
	}

	/**
	 * Gets information about the working hours of phone numbers to display lines and hours below the activity graph.
	 * If possible, displays the combined working hours of several numbers.
	 *
	 * @return array
	 */
	private function getWorkingHoursForGraph(): array
	{
		$workTimes = $this->getWorkTimesOfNumbers();

		$workingTimeOfNumbers = [];
		foreach ($workTimes as $numberIndex => $workTime)
		{
			$workingTimeOfNumbers[$numberIndex] = TimeHelper::formatNumberWorkTime($workTime);
		}

		if (!$workingTimeOfNumbers)
		{
			return [
				'active' => $this->getDisplayedHours(),
			];
		}

		$numbersWithReversedWorkTime = count(array_filter($workingTimeOfNumbers, static function($time) {
			return $time['TO'] < $time['FROM'];
		}));

		if ($numbersWithReversedWorkTime !== 0 && count($workTimes) > 1)
		{
			return [
				'active' => $this->getDisplayedHours(),
			];
		}

		$workTimeFrom = min(array_column($workingTimeOfNumbers, 'FROM'));
		$workTimeTo = max(array_column($workingTimeOfNumbers, 'TO'));

		return [
			'active' => $this->getActiveHoursByWorktime($workTimeFrom, $workTimeTo),
			'tooltip' => [
				$workTimeFrom,
				$workTimeTo
			]
		];
	}

	/**
	 * Gets information about the working hours of numbers from the report filter.
	 * Returns the total working time if no filter is specified.
	 *
	 * @return array
	 */
	private function getWorkTimesOfNumbers(): array
	{
		$query = ConfigTable::query();
		$query->setSelect([
			'TIMEZONE' => 'WORKTIME_TIMEZONE',
			'FROM' => 'WORKTIME_FROM',
			'TO' => 'WORKTIME_TO',
			'PORTAL_NUMBER' => 'number.NUMBER',
			'SIP_NUMBER' => 'SEARCH_ID'
		]);

		$query->registerRuntimeField(new ReferenceField(
			'number',
			NumberTable::getEntity(),
			Join::on('this.ID', 'ref.CONFIG_ID')
		));

		$query->registerRuntimeField(new ReferenceField(
			'sip',
			SipTable::getEntity(),
			Join::on('this.ID', 'ref.CONFIG_ID')
		));

		$query->where('WORKTIME_ENABLE', '=', 'Y');

		$portalNumbers = $this->getFilterParameters()['PORTAL_NUMBER'] ?? null;
		if ($portalNumbers)
		{
			$query->where(Query::filter()
					->logic('or')
					->whereIn('PORTAL_NUMBER', $portalNumbers)
					->whereIn('SIP_NUMBER', $portalNumbers)
			);
		}

		return $query->exec()->fetchAll();
	}

	/**
	 * Gets a map of active hours to display below the activity graph.
	 *
	 * @param $workTimeFrom
	 * @param $workTimeTo
	 *
	 * @return array
	 */
	private function getActiveHoursByWorktime($workTimeFrom, $workTimeTo): array
	{
		$activeHours = [];
		for ($i = 1; $i <= 24; $i++)
		{
			$hour = [
				'id' => $i,
				'name' => $i
			];

			if ($i === 0 || $i === 24 || ($i) % 6 === 0)
			{
				$hour['show'] = true;
			}

			if (($workTimeFrom < $workTimeTo) && ($i > $workTimeFrom && $i <= $workTimeTo))
			{
				$hour['active'] = true;
			}
			elseif (($workTimeFrom > $workTimeTo) && !($i <= $workTimeFrom && $i > $workTimeTo))
			{
				//If after converting time zones, the start time is later than the end.
				$hour['active'] = true;
			}

			$activeHours[] = $hour;
		}

		if (fmod($workTimeFrom, 1) != 0)
		{
			$firstHourIndex = (int)$workTimeFrom;
			$activeHours[$firstHourIndex]['active'] = true;
			$activeHours[$firstHourIndex]['firstHalf'] = true;
		}

		if (fmod($workTimeTo, 1) != 0)
		{
			$lastHourIndex = (int)$workTimeTo;
			$activeHours[$lastHourIndex]['active'] = true;
			$activeHours[$lastHourIndex]['lastHalf'] = true;
		}

		if (fmod($workTimeTo, 1) >= 0.31)
		{
			$hour['active'] = true;
			unset($activeHours[$lastHourIndex]['firstHalf'], $activeHours[$lastHourIndex]['lastHalf']);
		}

		return $activeHours;
	}

	private function getDisplayedHours(): array
	{
		$displayedHours = [];
		for ($i = 1; $i <= 24; $i++)
		{
			$hour = [
				'id' => $i,
				'name' => $i
			];

			if ($i === 0 || $i === 24 || ($i) % 6 === 0)
			{
				$hour['show'] = true;
			}

			$displayedHours[] = $hour;
		}

		return $displayedHours;
	}
}