<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\CallDynamics;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Integration\Report\Handler\Base;
use Bitrix\Voximplant\StatisticTable;
use CVoxImplantMain;

/**
 * Class CallDynamicsGrid
 * @package Bitrix\Voximplant\Integration\Report\Handler\CallDynamics
 */
class CallDynamicsGrid extends Base implements IReportMultipleData
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (!$calculatedData['report'])
		{
			return [];
		}

		$filterParameters = $this->getFilterParameters();

		$startDate = $calculatedData['startDate'];
		$finishDate = $calculatedData['finishDate'];
		$this->preloadUserInfo(array_column($calculatedData['report'], 'PORTAL_USER_ID'));

		$result = [];
		foreach ($calculatedData['report'] as $row)
		{
			$user = $this->getUserInfo($row['PORTAL_USER_ID']);

			$result[] = [
				'value' => [
					'USER_NAME' => $user['name'],
					'USER_ICON' => $user['icon'],
					'INCOMING' => $row['CALL_INCOMING'],
					'OUTGOING' => $row['CALL_OUTGOING'],
					'MISSED' => $row['CALL_MISSED'],
					'CALLBACK' => $row['CALL_CALLBACK'],
					'COUNT' => $row['CALL_COUNT'],
					'DYNAMICS' => $this->formatPeriodCompare($row['CALL_COUNT_COMPARE']),
				],
				'url' => [
					'INCOMING' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => [
							CVoxImplantMain::CALL_INCOMING,
							CVoxImplantMain::CALL_INCOMING_REDIRECT,
						],
						'STATUS' => self::CALL_STATUS_SUCCESS,
						'START_DATE_from' => $startDate,
						'START_DATE_to' => $finishDate,
					]),
					'OUTGOING' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
						'START_DATE_from' => $startDate,
						'START_DATE_to' => $finishDate,
					]),
					'MISSED' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'STATUS' => self::CALL_STATUS_FAILURE,
						'START_DATE_from' => $startDate,
						'START_DATE_to' => $finishDate,
					]),
					'CALLBACK' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_CALLBACK,
						'START_DATE_from' => $startDate,
						'START_DATE_to' => $finishDate,
					]),
					'COUNT' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'PHONE_NUMBER' => $filterParameters['PHONE_NUMBER'],
						'START_DATE_from' => $startDate,
						'START_DATE_to' => $finishDate,
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function prepare()
	{
		if (!$this->isCurrentUserHasAccess())
		{
			return [];
		}

		$filterParameters = $this->getFilterParameters();

		$startDate = new DateTime($filterParameters['TIME_PERIOD_from']);
		$finishDate = new DateTime($filterParameters['TIME_PERIOD_to']);

		$previousPeriod = $this->getPreviousPeriod($startDate, $finishDate);
		$previousStartDate = $previousPeriod['from'];
		$previousFinishDate = $previousPeriod['to'];

		return [
			'startDate' => $filterParameters['TIME_PERIOD_from'],
			'finishDate' => $filterParameters['TIME_PERIOD_to'],
			'report' => $this->getQueryForReport(
				$startDate,
				$finishDate,
				$previousStartDate,
				$previousFinishDate,
				$filterParameters)->exec()->fetchAll()
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getQueryForReport($startDate, $finishDate, $previousStartDate, $previousFinishDate, $filterParameters): Query
	{
		$subQuery = $this->getBaseQuery($previousStartDate, $previousFinishDate, $filterParameters);
		$query = $this->getBaseQuery($startDate, $finishDate, $filterParameters);

		$query->registerRuntimeField(new ReferenceField(
			'previous',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery),
			Join::on('this.PORTAL_USER_ID', 'ref.PORTAL_USER_ID')
		));

		$this->addCallTypeCompareField($query, 'CALL_COUNT');

		return $query;
	}

	/**
	 * Creates a basic query to sample the workload of employees.
	 *
	 * @param $startDate
	 * @param $finishDate
	 * @param $filterParameters
	 *
	 * @return Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getBaseQuery($startDate, $finishDate, $filterParameters)
	{
		$query = StatisticTable::query();
		$query->addSelect('PORTAL_USER_ID');

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addCallTypeField($query, CallType::INCOMING, 'CALL_INCOMING');
		$this->addCallTypeField($query, CallType::OUTGOING, 'CALL_OUTGOING');
		$this->addCallTypeField($query, CallType::MISSED, 'CALL_MISSED');
		$this->addCallTypeField($query, CallType::CALLBACK, 'CALL_CALLBACK');
		$this->addCallTypeField($query, CallType::ALL, 'CALL_COUNT');
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);

		return $query;
	}
}