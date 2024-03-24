<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\CallDynamics;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Integration\Report\Handler\Base;
use Bitrix\Voximplant\StatisticTable;
use CVoxImplantMain;

/**
 * Class CallDynamicsGraph
 * @package Bitrix\Voximplant\Integration\Report\Handler\CallDynamics
 */
class CallDynamicsGraph extends Base implements IReportMultipleData
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (empty($calculatedData))
		{
			return [];
		}

		$result = [];
		foreach ($calculatedData as $row)
		{
			$date = $this->getDateForUrl($row['DATE']);

			$result[] = [
				'value' => [
					'DATE' => $this->formatDateForGraph($date['date']),
					'INCOMING' => $row['CALL_INCOMING'],
					'OUTGOING' => $row['CALL_OUTGOING'],
					'MISSED' => $row['CALL_MISSED'],
					'CALLBACK' => $row['CALL_CALLBACK'],
					'INCOMING_COMPARE' => $row['CALL_INCOMING_COMPARE'],
					'OUTGOING_COMPARE' => $row['CALL_OUTGOING_COMPARE'],
					'MISSED_COMPARE' => $row['CALL_MISSED_COMPARE'],
					'CALLBACK_COMPARE' => $row['CALL_CALLBACK_COMPARE'],
				],
				'url' => [
					'INCOMING' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => [
							CVoxImplantMain::CALL_INCOMING,
							CVoxImplantMain::CALL_INCOMING_REDIRECT,
						],
						'STATUS' => self::CALL_STATUS_SUCCESS,
						'START_DATE_from' => $date['start'],
						'START_DATE_to' => $date['finish']
					]),
					'OUTGOING' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
						'START_DATE_from' => $date['start'],
						'START_DATE_to' => $date['finish']
					]),
					'MISSED' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'STATUS' => self::CALL_STATUS_FAILURE,
						'START_DATE_from' => $date['start'],
						'START_DATE_to' => $date['finish']
					]),
					'CALLBACK' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_CALLBACK,
						'STATUS' => self::CALL_STATUS_SUCCESS,
						'START_DATE_from' => $date['start'],
						'START_DATE_to' => $date['finish']
					]),
				]
			];
		}

		return $result;
	}

	public function getMultipleDemoData()
	{

	}

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

		return $this->getQueryForReport()->exec()->fetchAll();
	}

	/**
	 * Creates a query to select data based on a filter.
	 *
	 * @return Query
	 */
	public function getQueryForReport(): Query
	{
		$filterParameters = $this->getFilterParameters();

		$startDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD_from']);
		$finishDate = DateTime::createFromUserTime($filterParameters['TIME_PERIOD_to']);

		$previousPeriod = $this->getPreviousPeriod($startDate, $finishDate);
		$previousStartDate = $previousPeriod['from'];
		$previousFinishDate = $previousPeriod['to'];
		$dateDifference = $this->getDateInterval($filterParameters['TIME_PERIOD_datesel'], $previousPeriod['diff']);

		$subQuery = StatisticTable::query();

		$this->addDateWithGrouping($subQuery, true);
		$subQuery->addSelect('PREVIOUS_DATE');
		if (!empty($filterParameters['PORTAL_USER_ID']))
		{
			$subQuery->addSelect('PORTAL_USER_ID');
		}

		$this->addToQueryFilterCase($subQuery, $filterParameters);
		$this->addCallTypeField($subQuery, CallType::INCOMING, 'CALL_INCOMING');
		$this->addCallTypeField($subQuery, CallType::OUTGOING, 'CALL_OUTGOING');
		$this->addCallTypeField($subQuery, CallType::MISSED, 'CALL_MISSED');
		$this->addCallTypeField($subQuery, CallType::CALLBACK, 'CALL_CALLBACK');
		$subQuery->whereBetween('CALL_START_DATE', $previousStartDate, $previousFinishDate);

		$this->addIntervalByDatasel($subQuery, $filterParameters['TIME_PERIOD_datesel'], $dateDifference);

		$query = StatisticTable::query();

		$this->addDateWithGrouping($query, true);
		if (!empty($filterParameters['PORTAL_USER_ID']))
		{
			$query->addSelect('PORTAL_USER_ID');
		}

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addCallTypeField($query, CallType::INCOMING, 'CALL_INCOMING', true);
		$this->addCallTypeField($query, CallType::OUTGOING, 'CALL_OUTGOING', true);
		$this->addCallTypeField($query, CallType::MISSED, 'CALL_MISSED', true);
		$this->addCallTypeField($query, CallType::CALLBACK, 'CALL_CALLBACK', true);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);

		$query->registerRuntimeField(new ReferenceField(
			'previous',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery),
			Join::on('this.DATE', 'ref.PREVIOUS_DATE')
		));

		$this->addCallTypeCompareField($query, 'CALL_INCOMING');
		$this->addCallTypeCompareField($query, 'CALL_OUTGOING');
		$this->addCallTypeCompareField($query, 'CALL_MISSED');
		$this->addCallTypeCompareField($query, 'CALL_CALLBACK');

		return $query;
	}
}