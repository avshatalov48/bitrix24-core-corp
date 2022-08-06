<?php

namespace Bitrix\Crm\Integration\Report\Handler\Customers;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Crm\Integration\Report\Handler;
use Bitrix\Crm\DealTable;

class FinancialRatingGrid extends FinancialRating
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();

		$result = [];
		foreach ($calculatedData as $row)
		{
			$key = $row['OWNER_TYPE'] . "_" . $row['OWNER_ID'];
			$result[$key] = [
				'value' => [
					'ownerType' => $row['OWNER_TYPE'],
					'ownerId' => $row['OWNER_ID'],
					'successDealCount' => $row['WON_COUNT'],
					'successDealCountPrev' => $row['PREV_WON_COUNT'],
					'totalDealCount' => $row['TOTAL_COUNT'],
					'successDealAmount' => $row['WON_AMOUNT'],
					'successDealAmountPrev' => $row['PREV_WON_AMOUNT'],
				],
				'targetUrl' => [
					'totalDealCount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'OWNER_TYPE' => $row['OWNER_TYPE'],
						'OWNER_ID' => $row['OWNER_ID'],
					]),
					'successDealCount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'OWNER_TYPE' => $row['OWNER_TYPE'],
						'OWNER_ID' => $row['OWNER_ID'],
						'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
					]),
					'successDealAmount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'OWNER_TYPE' => $row['OWNER_TYPE'],
						'OWNER_ID' => $row['OWNER_ID'],
						'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
					]),
				]
			];
		}

		return $result;
	}

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();
		$query = DealTable::query();
		$query->addSelect('ID');

		$ownerType = $requestParameters['OWNER_TYPE'];
		$ownerId = $requestParameters['OWNER_ID'];
		if($ownerType === \CCrmOwnerType::CompanyName)
		{
			$query->where('COMPANY_ID', $ownerId);
		}
		else
		{
			$query->where('CONTACT_ID', $ownerId);
			$query->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'));
		}

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		foreach ($requestParameters as $parameter => $value)
		{
			switch ($parameter)
			{
				case 'STAGE_SEMANTIC_ID':
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

	protected function addTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = ($timePeriodValue['to'] instanceof DateTime) ? $timePeriodValue['to'] : new DateTime($timePeriodValue['to']);
			$fromDateValue = ($timePeriodValue['from'] instanceof DateTime) ? $timePeriodValue['from'] : new DateTime($timePeriodValue['from']);

			$query->where("DATE_CREATE", '<=', $toDateValue);
			$query->whereBetween("MOVED_TIME", $fromDateValue, $toDateValue);
		}
	}

	protected function isConversionCalculateMode()
	{
		return true;
	}

	public function getMultipleDemoData()
	{
		return [];
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
