<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\MissedReaction;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Model\StatisticMissedTable;
use Bitrix\Voximplant\Integration\Report\Handler\Base;

/**
 * Class MissedReaction
 * @package Bitrix\Voximplant\Integration\Report\Handler\MissedReaction
 */
class MissedReaction extends Base implements IReportMultipleData
{
	/**
	 * Prepares report data.
	 *
	 * @return array|mixed
	 * @throws ArgumentException
	 * @throws ObjectException
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
	 * @throws ArgumentException
	 * @throws SystemException
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

		$query->addSelect('AVG_RESPONSE_TIME_COMPARE');
		$query->registerRuntimeField(new ExpressionField(
			'AVG_RESPONSE_TIME_COMPARE',
			'%s - %s',
			['AVG_RESPONSE_TIME', 'previous.AVG_RESPONSE_TIME']
		));

		$query->addOrder('CALL_MISSED', 'DESC');

		return $query;
	}

	public function getBaseQuery($startDate, $finishDate, $filterParameters)
	{
		$query = StatisticMissedTable::query();

		$query->addSelect('PORTAL_USER_ID');
		$query->addSelect('CALL_MISSED');
		$query->addSelect('UNANSWERED');
		$query->addSelect('AVG_RESPONSE_TIME');

		$this->addToQueryFilterCase($query, $filterParameters);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);
		$query->addGroup('PORTAL_USER_ID');

		$query->registerRuntimeField(new ExpressionField(
			'CALL_MISSED',
			'count(%s)',
			['ID']
		));

		$query->registerRuntimeField(new ExpressionField(
			'UNANSWERED',
			'(sum(if(DATE_SUB(%s, INTERVAL 24 HOUR) <= %s, 0, 1)))',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE'])
		);

		$query->registerRuntimeField(new ExpressionField(
			'AVG_RESPONSE_TIME',
			'round(sum(if(%s is not null, TIMESTAMPDIFF(MINUTE, %s, %s), 0)) / (sum(if(%s is not null, if(DATE_SUB(%s, INTERVAL 24 HOUR) <= %s, 1, 0), 0))))',
			[
				'CALLBACK_CALL_START_DATE',
				'CALL_START_DATE',
				'CALLBACK_CALL_START_DATE',
				'CALLBACK_CALL_START_DATE',
				'CALLBACK_CALL_START_DATE',
				'CALL_START_DATE',
			])
		);

		return $query;
	}

	/**
	 * @param $requestParameters
	 *
	 * @return Query
	 * @throws ArgumentException
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public function prepareEntityListFilter($requestParameters): Query
	{
		$query = StatisticMissedTable::query();
		$fields = StatisticMissedTable::getEntity()->getScalarFields();

		foreach ($fields as $field)
		{
			$query->addSelect($field->getName());
		}

		$requestFilter = $requestParameters->toArray();
		if ($requestFilter['UNANSWERED'] === 'Y')
		{
			$query->registerRuntimeField(new ExpressionField(
				'UNANSWERED',
				"if(DATE_SUB(%s, INTERVAL 24 HOUR) <= %s, 'N', 'Y')",
				['CALLBACK_CALL_START_DATE', 'CALL_START_DATE'])
			);

			$query->where('UNANSWERED', '=', 'Y');
		}

		$sliderFilterParameters = $this->mergeRequestWithReportFilter($requestFilter);

		$this->addToQueryFilterCase($query, $sliderFilterParameters);

		$query->whereBetween(
			'CALL_START_DATE',
			DateTime::createFromUserTime($sliderFilterParameters['TIME_PERIOD_from']),
			DateTime::createFromUserTime($sliderFilterParameters['TIME_PERIOD_to'])
		);

		return $query;
	}

	/**
	 * Converts data from a report handler for a grid
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (!$calculatedData['report'])
		{
			return [];
		}

		$this->preloadUserInfo(array_column($calculatedData['report'], 'PORTAL_USER_ID'));

		$result = [];
		foreach ($calculatedData['report'] as $row)
		{
			$user = $this->getUserInfo($row['PORTAL_USER_ID'], ['avatarWidth' => 60, 'avatarHeight' => 60]);

			$result[] = [
				'value' => [
					'USER_NAME' => $user['name'],
					'USER_ICON' => $user['icon'],
					'MISSED' => $row['CALL_MISSED'],
					'UNANSWERED' => $row['UNANSWERED'],
					'AVG_RESPONSE_TIME' => $row['AVG_RESPONSE_TIME'],
					'AVG_RESPONSE_TIME_FORMATTED' => $this->formatDurationByMinutes($row['AVG_RESPONSE_TIME']),
					'DYNAMICS' => $row['AVG_RESPONSE_TIME_COMPARE'],
					'DYNAMICS_FORMATTED' => $this->formatDurationByMinutes(abs($row['AVG_RESPONSE_TIME_COMPARE'])),
				],
				'url' => [
					'MISSED' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
					]),
					'UNANSWERED' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'UNANSWERED' => 'Y'
					]),
				]
			];
		}

		return $result;
	}

	public function getMultipleDemoData()
	{

	}
}