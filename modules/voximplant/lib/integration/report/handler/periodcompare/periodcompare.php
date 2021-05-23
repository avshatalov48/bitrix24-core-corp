<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\PeriodCompare;

use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\StatisticTable;
use Bitrix\Voximplant\Integration\Report\Handler\Base;

/**
 * Class PeriodCompare
 * @package Bitrix\Voximplant\Integration\Report\Handler\PeriodCompare
 */
abstract class PeriodCompare extends Base
{
	public function prepare()
	{
		if (!$this->isCurrentUserHasAccess())
		{
			return [];
		}

		return $this->getQueryForReport()->exec()->fetchAll();
	}

	/**
	 * Creates a query to select data based on a filter.
	 *
	 * @return Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getQueryForReport(): Query
	{
		$filterParameters = $this->getFilterParameters();

		$startDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD_from']);
		$finishDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD_to']);

		$previousStartDate = DateTime::createFromUserTime($filterParameters['PREVIOUS_TIME_PERIOD_from']);
		$previousFinishDate = DateTime::createFromUserTime($filterParameters['PREVIOUS_TIME_PERIOD_to']);

		$secondDifference = $this->getDifferenceInSeconds($previousStartDate, $startDate);
		$dateDifference = $this->getDateInterval($filterParameters['TIME_PERIOD_datesel'], $secondDifference);

		$subQuery = StatisticTable::query();
		$this->addDateWithGrouping($subQuery, true);
		$subQuery->addSelect('PREVIOUS_DATE');
		if ($filterParameters['PORTAL_USER_ID'])
		{
			$subQuery->addSelect('PORTAL_USER_ID');
		}

		$this->addToQueryFilterCase($subQuery, $filterParameters);
		$this->addCallTypeField($subQuery, $filterParameters['INCOMING'], 'CALL_COUNT');
		$subQuery->whereBetween('CALL_START_DATE', $previousStartDate, $previousFinishDate);

		$this->addIntervalByDatasel($subQuery, $filterParameters['TIME_PERIOD_datesel'], $dateDifference);

		$query = StatisticTable::query();
		$this->addDateWithGrouping($query, true);
		$query->addSelect('previous.DATE', 'PREVIOUS_DATE');
		if ($filterParameters['PORTAL_USER_ID'])
		{
			$query->addSelect('PORTAL_USER_ID');
		}

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addCallTypeField($query, $filterParameters['INCOMING'], 'CALL_COUNT',true);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);

		$query->registerRuntimeField(new ReferenceField(
			'previous',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery),
			Join::on('this.DATE', 'ref.PREVIOUS_DATE')
		));

		$this->addCallTypeCompareField($query, 'CALL_COUNT');

		return $query;
	}

	/**
	 * Gets parameters for further insertion into the request
	 *
	 * @param $startDate
	 * @param $finishDate
	 *
	 * @return mixed
	 */
	protected function getUrlParams($startDate, $finishDate)
	{
		$filterParameters = $this->getFilterParameters();

		switch ($filterParameters['INCOMING'])
		{
			case CallType::INCOMING:
				$urlParams['INCOMING'] = $filterParameters['INCOMING'];
				$urlParams['STATUS'] = self::CALL_STATUS_SUCCESS;
				break;
			case CallType::OUTGOING:
			case CallType::CALLBACK:
				$urlParams['INCOMING'] = $filterParameters['INCOMING'];
				break;
			case CallType::MISSED:
				$urlParams['INCOMING'] = CallType::INCOMING;
				$urlParams['STATUS'] = self::CALL_STATUS_FAILURE;
				break;
		}

		if ($filterParameters['PORTAL_USER_ID'])
		{
			$urlParams['PORTAL_USER_ID'] = $filterParameters['PORTAL_USER_ID'];
		}

		if ($filterParameters['PORTAL_NUMBER'])
		{
			$urlParams['PORTAL_NUMBER'] = $filterParameters['PORTAL_NUMBER'];
		}

		if ($filterParameters['PHONE_NUMBER'])
		{
			$urlParams['PHONE_NUMBER'] = $filterParameters['PHONE_NUMBER'];
		}

		$urlParams['START_DATE_from'] = $startDate;
		$urlParams['START_DATE_to'] = $finishDate;

		return $urlParams;
	}
}