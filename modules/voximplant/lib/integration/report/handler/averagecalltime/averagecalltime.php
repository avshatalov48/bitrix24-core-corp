<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\AverageCallTime;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Voximplant\Integration\Report\Handler\Base;
use Bitrix\Voximplant\StatisticTable;

/**
 * Class AverageCallTime
 * @package Bitrix\Voximplant\Integration\Report\Handler\AverageCallTime
 */
class AverageCallTime extends Base implements IReportMultipleData
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

		$query->addSelect('AVG_CALL_TIME_COMPARE');
		$query->registerRuntimeField(new ExpressionField(
			'AVG_CALL_TIME_COMPARE',
			'%s - %s',
			['AVG_CALL_TIME', 'previous.AVG_CALL_TIME']
		));

		$query->addOrder('AVG_CALL_TIME', 'DESC');

		return $query;
	}

	public function getBaseQuery($startDate, $finishDate, $filterParameters)
	{
		$query = StatisticTable::query();
		$query->addSelect('PORTAL_USER_ID');
		$query->addSelect('AVG_CALL_TIME');

		$query->registerRuntimeField(new ExpressionField(
			'AVG_CALL_TIME',
			'avg(%s)',
			['CALL_DURATION']
		));

		$this->addToQueryFilterCase($query, $filterParameters);
		$query->whereBetween('CALL_START_DATE', $startDate, $finishDate);
		$query->addGroup('PORTAL_USER_ID');

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
		foreach ($calculatedData['report'] as $key => $row)
		{
			if ((int)$row['AVG_CALL_TIME'] === 0)
			{
				unset($calculatedData['report'][$key]);
				continue;
			}

			$user = $this->getUserInfo($row['PORTAL_USER_ID'], ['avatarWidth' => 60, 'avatarHeight' => 60]);

			$result[] = [
				'value' => [
					'USER_NAME' => $user['name'],
					'USER_ICON' => $user['icon'],
					'AVG_CALL_TIME' => $row['AVG_CALL_TIME'],
					'AVG_CALL_TIME_FORMATTED' => $this->formatDuration($row['AVG_CALL_TIME']),
					'DYNAMICS' => $this->formatPeriodCompare((int)$row['AVG_CALL_TIME_COMPARE']),
					'DYNAMICS_FORMATTED' => $this->formatDuration(abs((int)$row['AVG_CALL_TIME_COMPARE'])),
				],
			];
		}

		return $result;
	}

	public function getMultipleDemoData()
	{

	}
}