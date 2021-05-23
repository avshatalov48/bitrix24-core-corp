<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\CallActivity;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\StatisticTable;
use CVoxImplantMain;

/**
 * Class CallActivity
 * @package Bitrix\Voximplant\Integration\Report\Handler\CallActivity
 */
class CallActivityGrid extends CallActivity implements IReportMultipleData
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (!$calculatedData['report'])
		{
			return [];
		}

		$result = [];
		foreach ($calculatedData['report'] as $row)
		{
			$incoming = (int)$row['CALL_INCOMING'];
			$missed = (int)$row['CALL_MISSED'];

			if (!$incoming && !$missed)
			{
				continue;
			}

			$date = $this->getDateForUrl($row['DATE']);
			$result[] = [
				'value' => [
					'DATE' => $row['DATE'],
					'DATE_FORMATTED' => FormatDate(
						Context::getCurrent()->getCulture()->getShortDateFormat().' (D)',
						$date['date']
					),
					'INCOMING' => $incoming,
					'MISSED' => $missed,
				],
				'url' => [
					'INCOMING' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'STATUS' => self::CALL_STATUS_SUCCESS,
						'START_DATE_from' => $date['start'],
						'START_DATE_to' => $date['finish']
					]),
					'MISSED' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'STATUS' => self::CALL_STATUS_FAILURE,
						'START_DATE_from' => $date['start'],
						'START_DATE_to' => $date['finish']
					])
				]
			];
		}

		return $result;
	}

	public function getMultipleDemoData()
	{

	}

	/**
	 * @return array|mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
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
	 * @param $filterParameters
	 *
	 * @return Query
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getQueryForReport($startDate, $finishDate, $filterParameters): Query
	{
		$query = StatisticTable::query();

		$this->addDateWithGrouping($query);
		$this->addCallTypeField($query, CallType::INCOMING, 'CALL_INCOMING');
		$this->addCallTypeField($query, CallType::MISSED, 'CALL_MISSED');

		$this->addToQueryFilterCase($query, $filterParameters);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);

		return $query;
	}
}