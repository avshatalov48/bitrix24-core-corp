<?php


namespace Bitrix\Crm\Integration\Report\Handler\Managers;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Web\Uri;

abstract class Rating extends Deal
{
	protected const LIMIT = 25;
	protected const PERIOD_SEMANTIC_CURRENT = 'C';
	protected const PERIOD_SEMANTIC_PREVIOUS = 'S';

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();;
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?: 0;
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId))
		{
			return false;
		}

		$result = [];
		$mainQuery = $this->prepareQuery();
		$mainQuery->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);
		$mainQuery->setLimit(static::LIMIT);
		$mainQuery->setOrder(["DEAL_AMOUNT" => "DESC"]);

		foreach ($mainQuery->exec()->getIterator() as $row)
		{
			$result[$row['USER_ID']] = [
				'USER_ID' => $row['USER_ID'],
				'COUNT_WON' => $row['DEAL_COUNT'],
				'AMOUNT_WON' => $row['DEAL_AMOUNT'],
			];
		}

		if (count($result) > 0)
		{
			$totalQuery = $this->prepareQuery();
			$totalQuery->whereIn('ASSIGNED_BY_ID', array_keys($result));

			foreach ($totalQuery->exec()->getIterator() as $row)
			{
				$result[$row['USER_ID']]['COUNT_TOTAL'] = $row['DEAL_COUNT'];
				$result[$row['USER_ID']]['AMOUNT_TOTAL'] = $row['DEAL_AMOUNT'];
			}

			$prevPeriodQuery = $this->prepareQuery(self::PERIOD_SEMANTIC_PREVIOUS);
			$prevPeriodQuery->whereIn('ASSIGNED_BY_ID', array_keys($result));
			$prevPeriodQuery->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);
			foreach ($prevPeriodQuery->exec()->getIterator() as $row)
			{
				$result[$row['USER_ID']]['AMOUNT_WON_PREV'] = $row['DEAL_AMOUNT'];
				$result[$row['USER_ID']]['COUNT_WON_PREV'] = $row['DEAL_COUNT'];
			}

			$prevPeriodTotalQuery = $this->prepareQuery(self::PERIOD_SEMANTIC_PREVIOUS);
			$prevPeriodTotalQuery->whereIn('ASSIGNED_BY_ID', array_keys($result));
			foreach ($prevPeriodTotalQuery->exec()->getIterator() as $row)
			{
				$result[$row['USER_ID']]['AMOUNT_TOTAL_PREV'] = $row['DEAL_AMOUNT'];
				$result[$row['USER_ID']]['COUNT_TOTAL_PREV'] = $row['DEAL_COUNT'];
			}
		}

		return $result;
	}

	protected function isConversionCalculateMode()
	{
		return true;
	}

	protected function addTimePeriodToQuery(Query $query, $timePeriodValue, $periodSemantic = self::PERIOD_SEMANTIC_CURRENT)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = new DateTime($timePeriodValue['to']);
			$fromDateValue = new DateTime($timePeriodValue['from']);

			if($periodSemantic === self::PERIOD_SEMANTIC_PREVIOUS)
			{
				[$fromDateValue, $toDateValue] = self::getPreviousPeriod($fromDateValue, $toDateValue);
			}

			$query->where("DATE_CREATE", '<=', $toDateValue);
			$query->whereBetween("MOVED_TIME", $fromDateValue, $toDateValue);
		}
	}

	public function prepareQuery($periodSemantic = self::PERIOD_SEMANTIC_CURRENT)
	{
		$query = DealTable::query();

		$filterParameters = $this->getFilterParameters();
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD'], $periodSemantic);
		$this->addPermissionsCheck($query);

		$query
			->addSelect("ASSIGNED_BY_ID", "USER_ID")
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->addSelect(Query::expr()->sum("OPPORTUNITY_ACCOUNT"), "DEAL_AMOUNT");

		return $query;
	}

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();
		$query = DealTable::query();
		$query->addSelect('ID');

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		foreach ($requestParameters as $parameter => $value)
		{
			switch ($parameter)
			{
				case 'STAGE_SEMANTIC_ID':
				case 'ASSIGNED_BY_ID':
					$query->where($parameter, $value);
					break;
			}
		}

		return [
			'__JOINS' => [
				[
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') REP ON REP.ID = L.ID'
				]
			]
		];
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$uri = new Uri($baseUri);
		$uri->addParams([
			'from_analytics' => 'Y',
			'report_id' => $this->getReport()->getGId()
		]);

		if (!empty($params))
		{
			$uri->addParams($params);
		}
		return $uri->getUri();
	}
}
