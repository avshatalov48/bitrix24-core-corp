<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\EmployeesWorkload;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Integration\Report\Handler\Base;
use Bitrix\Voximplant\StatisticTable;

/**
 * Class EmployeesWorkload
 * @package Bitrix\Voximplant\Integration\Report\Handler\EmployeesWorkload
 */
abstract class EmployeesWorkload extends Base
{
	/**
	 * Prepares report data.
	 *
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

		$previousPeriod = $this->getPreviousPeriod($startDate, $finishDate);
		$previousStartDate = $previousPeriod['from'];
		$previousFinishDate = $previousPeriod['to'];

		$report = $this->getQueryForReport(
			$startDate,
			$finishDate,
			$previousStartDate,
			$previousFinishDate,
			$filterParameters
		)->exec()->fetchAll();

		return [
			'startDate' => $filterParameters['TIME_PERIOD_from'],
			'finishDate' => $filterParameters['TIME_PERIOD_to'],
			'report' => $report
		];
	}

	/**
	 * Creates a query to select data based on a filter.
	 *
	 * @param $startDate
	 * @param $finishDate
	 * @param $previousStartDate
	 * @param $previousFinishDate
	 * @param $filterParameters
	 *
	 * @return Query
	 */
	abstract protected function getQueryForReport($startDate, $finishDate, $previousStartDate, $previousFinishDate, $filterParameters): Query;

	/**
	 * Creates a basic query to sample the workload of employees.
	 *
	 * @param $startDate
	 * @param $finishDate
	 * @param $filterParameters
	 *
	 * @return Query
	 */
	protected function getBaseQuery($startDate, $finishDate, $filterParameters)
	{
		$query = StatisticTable::query();
		$query->addSelect('PORTAL_USER_ID');

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addCallTypeField($query, CallType::INCOMING, 'CALL_INCOMING');
		$this->addCallTypeField($query, CallType::OUTGOING, 'CALL_OUTGOING');
		$this->addCallTypeField($query, CallType::MISSED, 'CALL_MISSED');
		$this->addCallTypeField($query, CallType::ALL, 'CALL_COUNT');
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);

		return $query;
	}
}