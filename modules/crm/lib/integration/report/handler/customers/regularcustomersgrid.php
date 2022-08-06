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

/**
 * Class Deal
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class RegularCustomersGrid extends Handler\Deal
{
	protected const LIMIT = 10;

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();;
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?: 0;
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId))
		{
			return false;
		}

		$query = $this->prepareQuery();
		return Application::getConnection()->query($query)->fetchAll();
	}

	public function prepareQuery()
	{
		$filterParameters = $this->getFilterParameters();

		$query1 = DealTable::query();
		$query2 = DealTable::query();

		$this->addToQueryFilterCase($query1, $filterParameters);
		$this->addTimePeriodToQuery($query1, $filterParameters['TIME_PERIOD']);

		$this->addToQueryFilterCase($query2, $filterParameters);
		$this->addTimePeriodToQuery($query2, $filterParameters['TIME_PERIOD']);

		$this->addPermissionsCheck($query1);
		$this->addPermissionsCheck($query2);

		$query1
			->addSelect(new \Bitrix\Main\Entity\ExpressionField("OWNER_TYPE", "'CONTACT'"))
			->addSelect("CONTACT.ID", "OWNER_ID")
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->whereNotNull("CONTACT.ID")
			->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'));

		$query2
			->addSelect(new \Bitrix\Main\Entity\ExpressionField("OWNER_TYPE", "'COMPANY'"))
			->addSelect("COMPANY.ID", "OWNER_ID")
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->whereNotNull("COMPANY.ID");

		$query1->unionAll($query2);

		$limit = static::LIMIT;
		return "SELECT * FROM ({$query1->getQuery()}) t ORDER BY DEAL_COUNT DESC LIMIT {$limit}";
	}

	public function getMultipleData()
	{
		$filterParameters = $this->getFilterParameters();

		$calculatedData = $this->getCalculatedData();
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();

		$result = [];
		$contacts = [];
		$companies = [];

		foreach ($calculatedData as $row)
		{
			if($row['OWNER_TYPE'] == \CCrmOwnerType::ContactName)
			{
				$contacts[] = $row['OWNER_ID'];
			}
			else
			{
				$companies[] = $row['OWNER_ID'];
			}
			$key = $row['OWNER_TYPE'] . "_" . $row['OWNER_ID'];
			$result[$key] = [
				'value' => [
					'ownerType' => $row['OWNER_TYPE'],
					'ownerId' => $row['OWNER_ID'],
					'totalDealCount' => $row['DEAL_COUNT'],
					'successDealCount' => 0,
					'successDealAmount' => 0,
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

		if(count($contacts) > 0)
		{
			$query = DealTable::query();
			$this->addToQueryFilterCase($query, $filterParameters);
			$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
			$this->addPermissionsCheck($query);

			$query
				->addSelect(new \Bitrix\Main\Entity\ExpressionField("OWNER_TYPE", "'CONTACT'"))
				->addSelect("CONTACT.ID", "OWNER_ID")
				->addSelect("CURRENCY_ID")
				->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
				->addSelect(Query::expr()->sum("OPPORTUNITY"), "TOTAL_AMOUNT")
				->whereNotNull("CONTACT.ID")
				->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'))
				->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS)
				->whereIn("CONTACT.ID", $contacts);
			$cursor = $query->exec();

			while ($row = $cursor->fetch())
			{
				$key = $row['OWNER_TYPE'] . "_" . $row['OWNER_ID'];

				$result[$key]['value']['successDealCount'] += $row['DEAL_COUNT'];
				$result[$key]['value']['successDealAmount'] += \CCrmCurrency::ConvertMoney($row['TOTAL_AMOUNT'], $row['CURRENCY_ID'], $baseCurrency);
			}
		}

		if(count($companies) > 0)
		{
			$query = DealTable::query();
			$this->addToQueryFilterCase($query, $filterParameters);
			$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
			$this->addPermissionsCheck($query);

			$query
				->addSelect(new \Bitrix\Main\Entity\ExpressionField("OWNER_TYPE", "'COMPANY'"))
				->addSelect("COMPANY.ID", "OWNER_ID")
				->addSelect("CURRENCY_ID")
				->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
				->addSelect(Query::expr()->sum("OPPORTUNITY"), "TOTAL_AMOUNT")
				->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS)
				->whereIn("COMPANY.ID", $companies);
			$cursor = $query->exec();

			while ($row = $cursor->fetch())
			{
				$key = $row['OWNER_TYPE'] . "_" . $row['OWNER_ID'];

				$result[$key]['value']['successDealCount'] += $row['DEAL_COUNT'];
				$result[$key]['value']['successDealAmount'] += \CCrmCurrency::ConvertMoney($row['TOTAL_AMOUNT'], $row['CURRENCY_ID'], $baseCurrency);
			}
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
