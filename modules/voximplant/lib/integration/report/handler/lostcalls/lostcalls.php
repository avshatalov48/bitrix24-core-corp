<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\LostCalls;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Model\StatisticMissedTable;
use Bitrix\Voximplant\Integration\Report\Handler\Base;
use Bitrix\Voximplant\StatisticTable;

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

		$helper = Application::getConnection()->getSqlHelper();

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
			'SUM(CASE WHEN '.$helper->addDaysToDateTime(-1, '%s').' <= %s THEN 0 ELSE 1 END)',
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
			'SUM(CASE WHEN '.$helper->addDaysToDateTime(-1, '%s').' <= %s THEN 0 ELSE 1 END)',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE']
		));
		$query->registerRuntimeField(new ReferenceField(
			'previous',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery),
			Join::on('this.DATE', 'ref.PREVIOUS_DATE')
		));
		$query->registerRuntimeField(new ExpressionField(
			'LOST_CALLS_COUNT_COMPARE',
			'(CASE WHEN %1$s = 0 THEN null ELSE %1$s - %2$s END)',
			['LOST_CALLS_COUNT', 'previous.LOST_CALLS_COUNT']
		));

		return $query;
	}

	/**
	 * @param $requestParameters
	 *
	 * @return Query
	 */
	public function prepareEntityListFilter($requestParameters): Query
	{
		$missedQuery = StatisticMissedTable::query();
		$fields = StatisticMissedTable::getEntity()->getScalarFields();

		$helper = Application::getConnection()->getSqlHelper();

		foreach ($fields as $field)
		{
			$missedQuery->addSelect($field->getName());
		}

		$missedQuery->registerRuntimeField(new ExpressionField(
			'UNANSWERED',
			"CASE WHEN ".$helper->addDaysToDateTime(-1, '%s')." <= %s THEN 'N' ELSE 'Y' END",
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE'])
		);
		$missedQuery->where('UNANSWERED', '=', 'Y');

		$sliderFilterParameters = $this->mergeRequestWithReportFilter($requestParameters->toArray());

		$this->addToQueryFilterCase($missedQuery, $sliderFilterParameters);

		$missedQuery->whereBetween(
			'CALL_START_DATE',
			DateTime::createFromUserTime($requestParameters->get('START_DATE_from')),
			DateTime::createFromUserTime($requestParameters->get('START_DATE_to'))
		);

		//The calling code expects that this method will return a query to StatisticTable
		// and can add filters to its fields.
		// @see CVoximplantStatisticDetailComponent createReportSliderQuery
		$query = StatisticTable::query();
		$query->setSelect(['ID']);
		$query->registerRuntimeField(new Reference(
			'STATISTIC_MISSED',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($missedQuery),
			Join::on('this.ID', 'ref.ID'),
			['join_type' => Join::TYPE_INNER]
		));

		return $query;
	}

	/**
	 * Converts data from a report handler for a grid
	 *
	 * @return array
	 */
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