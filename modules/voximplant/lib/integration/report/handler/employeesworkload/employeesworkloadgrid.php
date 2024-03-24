<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\EmployeesWorkload;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use CVoxImplantMain;

/**
 * Class EmployeesWorkloadGrid
 * @package Bitrix\Voximplant\Integration\Report\Handler\EmployeesWorkload
 */
class EmployeesWorkloadGrid extends EmployeesWorkload implements IReportMultipleData
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (empty($calculatedData['report']))
		{
			return [];
		}

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
					'COUNT' => $row['CALL_COUNT'],
					'MISSED_DYNAMICS' => $this->formatPeriodCompare($row['CALL_MISSED_COMPARE']),
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
					]),
					'OUTGOING' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
					]),
					'MISSED' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'STATUS' => self::CALL_STATUS_FAILURE,
					]),
					'COUNT' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
					]),
				]
			];
		}

		return $result;
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
	protected function getQueryForReport($startDate, $finishDate, $previousStartDate, $previousFinishDate, $filterParameters): Query
	{
		$subQuery = $this->getBaseQuery($previousStartDate, $previousFinishDate, $filterParameters);
		$query = $this->getBaseQuery($startDate, $finishDate, $filterParameters);

		$query->registerRuntimeField(new ReferenceField(
			'previous',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery),
			Join::on('this.PORTAL_USER_ID', 'ref.PORTAL_USER_ID')
		));

		$this->addCallTypeCompareField($query, 'CALL_MISSED');
		$this->addCallTypeCompareField($query, 'CALL_COUNT');

		return $query;
	}

	public function getMultipleDemoData()
	{

	}
}