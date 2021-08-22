<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\LostCalls;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Model\StatisticMissedTable;
use Bitrix\Voximplant\Integration\Report\Handler\Base;
use CTimeZone;

/**
 * Class LostCalls
 * @package Bitrix\Voximplant\Integration\Report\Handler\LostCalls
 */
class LostCalls extends Base implements IReportMultipleData
{
	protected $reportFilterKeysForSlider = [
		'PHONE_NUMBER',
		'PORTAL_USER_ID'
	];

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

		return $this->getQueryForReport()->exec()->fetchAll();
	}

	/**
	 * Creates a query to select data based on a filter.
	 *
	 * @return Query
	 * @throws ArgumentException
	 * @throws ObjectException
	 * @throws SystemException
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

		$subQuery = StatisticMissedTable::query();
		$this->addDateWithGrouping($subQuery, true);
		$subQuery->addSelect('PREVIOUS_DATE');
		$subQuery->addSelect('LOST_CALLS_COUNT');

		$this->addToQueryFilterCase($subQuery, $filterParameters);
		$subQuery->whereBetween('CALL_START_DATE', $previousStartDate, $previousFinishDate);
		$this->addIntervalByDatasel($subQuery, $filterParameters['TIME_PERIOD_datesel'], $dateDifference);
		$subQuery->registerRuntimeField(new ExpressionField(
			'LOST_CALLS_COUNT',
			'(sum(if(DATE_SUB(%s, INTERVAL 24 HOUR) <= %s, 0, 1)))',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE']
		));

		$query = StatisticMissedTable::query();
		$this->addDateWithGrouping($query, true);
		$query->addSelect('LOST_CALLS_COUNT');
		$query->addSelect('LOST_CALLS_COUNT_COMPARE');

		$this->addToQueryFilterCase($query, $filterParameters);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);
		$query->registerRuntimeField(new ExpressionField(
			'LOST_CALLS_COUNT',
			'(sum(if(DATE_SUB(%s, INTERVAL 24 HOUR) <= %s, 0, 1)))',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE']
		));
		$query->registerRuntimeField(new ReferenceField(
			'previous',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery),
			Join::on('this.DATE', 'ref.PREVIOUS_DATE')
		));
		$query->registerRuntimeField(new ExpressionField(
			'LOST_CALLS_COUNT_COMPARE',
			'if(%s = 0, null, %s - %s)',
			['LOST_CALLS_COUNT', 'LOST_CALLS_COUNT', 'previous.LOST_CALLS_COUNT']
		));

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

		$query->registerRuntimeField(new ExpressionField(
			'UNANSWERED',
			"if(DATE_SUB(%s, INTERVAL 24 HOUR) <= %s, 'N', 'Y')",
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE'])
		);
		$query->where('UNANSWERED', '=', 'Y');

		$sliderFilterParameters = $this->mergeRequestWithReportFilter($requestParameters->toArray());

		$this->addToQueryFilterCase($query, $sliderFilterParameters);

		$query->whereBetween(
			'CALL_START_DATE',
			DateTime::createFromUserTime($requestParameters->get('START_DATE_from')),
			DateTime::createFromUserTime($requestParameters->get('START_DATE_to'))
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
		if (!$calculatedData)
		{
			return [];
		}

		$result = [];
		foreach ($calculatedData as $row)
		{
			$date = $this->getDateForUrl($row['DATE']);
			$result[] = [
				'value' => [
					'DATE' => $row['DATE'],
					'DATE_FORMATTED' => $this->formatDateForGrid($date['date']),
					'LOST_CALLS_COUNT' => $row['LOST_CALLS_COUNT'],
					'DYNAMICS' => $row['LOST_CALLS_COUNT_COMPARE'],
				],
				'url' => [
					'LOST_CALLS_COUNT' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
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
}