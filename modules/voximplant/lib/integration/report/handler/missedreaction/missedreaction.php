<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\MissedReaction;

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
 * Class MissedReaction
 * @package Bitrix\Voximplant\Integration\Report\Handler\MissedReaction
 */
class MissedReaction extends Base implements IReportMultipleData
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

		$helper = Application::getConnection()->getSqlHelper();

		$query->addSelect('PORTAL_USER_ID');
		$query->addSelect('CALL_MISSED');
		$query->addSelect('UNANSWERED');
		$query->addSelect('AVG_RESPONSE_TIME');

		$this->addToQueryFilterCase($query, $filterParameters);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);
		$query->addGroup('PORTAL_USER_ID');

		$query->registerRuntimeField(new ExpressionField(
			'CALL_MISSED',
			'COUNT(%s)',
			['ID']
		));

		$query->registerRuntimeField(new ExpressionField(
			'UNANSWERED',
			'SUM(CASE WHEN '.$helper->addDaysToDateTime(-1, '%s').' <= %s THEN 0 ELSE 1 END)',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE'])
		);

		$query->registerRuntimeField(new ExpressionField(
			'RESPONSE_TIME',
			'SUM(CASE WHEN %1$s is not null THEN TIMESTAMPDIFF(MINUTE, %2$s, %1$s) ELSE 0 END)',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE']
		));

		$query->registerRuntimeField(new ExpressionField(
			'CALLBACK_COUNT',
			'SUM(CASE WHEN %1$s is not null THEN (CASE WHEN '.$helper->addDaysToDateTime(-1, '%1$s').' <= %2$s THEN 1 ELSE 0 END) ELSE 0 END)',
			['CALLBACK_CALL_START_DATE', 'CALL_START_DATE']
		));

		$query->registerRuntimeField(new ExpressionField(
			'AVG_RESPONSE_TIME',
			'ROUND(%s / %s, 0)',
			['RESPONSE_TIME', 'CALLBACK_COUNT']
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
		$missedReactionQuery = StatisticMissedTable::query();
		$fields = StatisticMissedTable::getEntity()->getScalarFields();

		$helper = Application::getConnection()->getSqlHelper();

		foreach ($fields as $field)
		{
			$missedReactionQuery->addSelect($field->getName());
		}

		$requestFilter = $requestParameters->toArray();
		if ($requestFilter['UNANSWERED'] === 'Y')
		{
			$missedReactionQuery->registerRuntimeField(new ExpressionField(
				'UNANSWERED',
				"CASE WHEN ".$helper->addDaysToDateTime(-1, '%s')." <= %s THEN 'N' ELSE 'Y' END",
				['CALLBACK_CALL_START_DATE', 'CALL_START_DATE'])
			);

			$missedReactionQuery->where('UNANSWERED', '=', 'Y');
		}

		$sliderFilterParameters = $this->mergeRequestWithReportFilter($requestFilter);

		$this->addToQueryFilterCase($missedReactionQuery, $sliderFilterParameters);

		$missedReactionQuery->whereBetween(
			'CALL_START_DATE',
			DateTime::createFromUserTime($sliderFilterParameters['TIME_PERIOD_from']),
			DateTime::createFromUserTime($sliderFilterParameters['TIME_PERIOD_to'])
		);

		//The calling code expects that this method will return a query to StatisticTable
		// and can add filters to its fields.
		// @see CVoximplantStatisticDetailComponent createReportSliderQuery
		$query = StatisticTable::query();
		$query->setSelect(['ID']);
		$query->registerRuntimeField(new Reference(
			'STATISTIC_MISSED',
			\Bitrix\Main\Entity\Base::getInstanceByQuery($missedReactionQuery),
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
		if (empty($calculatedData['report']))
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