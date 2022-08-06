<?php

namespace Bitrix\Crm\Integration\Report\Handler\Customers;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

use Bitrix\Crm\DealTable;

class RegularCustomers extends Deal
{
	const GROUPS = [
		[1, 1],
		[2, 2],
		[3, 3],
		[4, 4],
		[5, 5],
		[6, 6],
		[7, 7],
		[8, 8],
		[9, 9],
		[10, 19],
		[20, 29],
		[30, 39],
		[40, 0],
	];

	const COLORS = [
		"#2FC6F6",
		"#55D0E0",
		"#9DCF00",
		"#FFA900",
		"#FF5752",
		"#2066B0",
		"#828B95",
		"#00B4AC",
		"#C8754D",
		"#1EAE43",
		"#4093E7",
		"#FF799C",
		"#B7EB81",
		"#C35AD7",
	];

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
		//return $this->prepareTestData();
	}

	protected function isConversionCalculateMode()
	{
		return true;
	}

	protected function addTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = new DateTime($timePeriodValue['to']);
			$fromDateValue = new DateTime($timePeriodValue['from']);

			$query->whereBetween("MOVED_TIME", $fromDateValue, $toDateValue);
		}
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
			->addSelect("CURRENCY_ID")
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->addSelect(Query::expr()->sum("OPPORTUNITY"), "TOTAL_AMOUNT")
			->whereNotNull("CONTACT.ID")
			->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS)
			->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'));

		$query2
			->addSelect(new \Bitrix\Main\Entity\ExpressionField("OWNER_TYPE", "'COMPANY'"))
			->addSelect("COMPANY.ID", "OWNER_ID")
			->addSelect("CURRENCY_ID")
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->addSelect(Query::expr()->sum("OPPORTUNITY"), "TOTAL_AMOUNT")
			->whereNotNull("COMPANY.ID")
			->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);

		$query1->unionAll($query2);

		$mainQuery = "
			SELECT 
				OWNER_TYPE, 
				CURRENCY_ID, 
				DEAL_COUNT, 
				COUNT(DEAL_COUNT) AS CLIENT_COUNT, 
				SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT 
			FROM ({$query1->getQuery()}) t 
			GROUP BY OWNER_TYPE, CURRENCY_ID, DEAL_COUNT
		";

		return $mainQuery;
	}

	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$totalEarnings = 0;

		$normalizedData = [];
		foreach ($calculatedData as $row)
		{
			[$type, $currency, $dealCount, $clientCount, $amount] = array_values($row);

			if($currency != $baseCurrency)
			{
				$amount = \CCrmCurrency::ConvertMoney($amount, $currency, $baseCurrency);
			}

			[$min, $max] = static::findGroup($dealCount);
			$group = $min . "_" . $max;

			if(!$normalizedData[$group])
			{
				$normalizedData[$group] = [
					'countTotal' => 0,
					'countCompany' => 0,
					'countContact' => 0,
					'amountCompany' => 0,
					'amountContact' => 0
				];
			}

			$normalizedData[$group]['countTotal'] += $clientCount;
			$totalEarnings += $amount;
			if($type == \CCrmOwnerType::ContactName)
			{
				$normalizedData[$group]['countContact'] += $clientCount;
				$normalizedData[$group]['amountContact'] += $amount;
			}
			else // if ($type == \CCrmOwnerType::CompanyName)
			{
				$normalizedData[$group]['countCompany'] += $clientCount;
				$normalizedData[$group]['amountCompany'] += $amount;
			}
		}

		$items = [];
		$labels = [];
		$i = 0;
		foreach ($normalizedData as $group => $fields)
		{
			[$min, $max] = explode("_", $group);
			$labels[$group] = static::getGroupLabel($min, $max);

			$totalAmount = $fields['amountCompany'] + $fields['amountContact'];
			$totalAmountFormatted = \CCrmCurrency::MoneyToString($totalAmount, $baseCurrency);

			$items[] = [
				'groupBy' => $group,
				'value' => $fields['countTotal'],
				'balloon' => [
					'color' => static::COLORS[$i++ % 14],
					'countContact' => $fields['countContact'],
					'countCompany' => $fields['countCompany'],
					'contactsUrl' => $this->getTargetUrl('/crm/contact/analytics/list/', ['OWNER_TYPE' => \CCrmOwnerType::ContactName, 'DEALS_from' => $min, 'DEALS_to' => $max]),
					'companiesUrl' => $this->getTargetUrl('/crm/company/analytics/list/', ['OWNER_TYPE' => \CCrmOwnerType::CompanyName, 'DEALS_from' => $min, 'DEALS_to' => $max]),
					'countClients' => $fields['countContact'] + $fields['countCompany'],
					'totalAmountFormatted' => $totalAmountFormatted,
					'earningsPercent' => $totalEarnings > 0 ? round(($totalAmount / $totalEarnings) * 100, 2) : 0
				]
				//"label" => $fields['countTotal']
			];
		}

		$result = [
			"items" => $items,
			"config" => [
				"groupsLabelMap" => $labels,
				"reportTitle" => $this->getFormElementValue("label"),
				"reportColor" => $this->getFormElementValue("color"),
				"reportTitleShort" => $this->getFormElementValue("label"),
				"reportTitleMedium" => $this->getFormElementValue("label"),
			]
		];
		return $result;
	}

	public static function findGroup(int $dealCount)
	{
		foreach (static::GROUPS as [$min, $max])
		{
			if($min == $max && $min == $dealCount)
			{
				return [$min, $max];
			}
			if($max > $min && $dealCount <= $max && $dealCount >= $min )
			{
				return [$min, $max];
			}
			if($max == 0 && $dealCount >= $min)
			{
				return [$min, $max];
			}
		}
		throw new SystemException("Could not find suitable group for {$dealCount} deals");
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

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();

		$ownerType = $requestParameters['OWNER_TYPE'];
		$query = DealTable::query();

		if($ownerType == \CCrmOwnerType::CompanyName)
		{
			$query->addSelect('COMPANY_ID', 'OWNER_ID');
			$query->where('COMPANY_ID', '>', 0);
		}
		else
		{
			$query->addSelect('CONTACT_ID', 'OWNER_ID');
		}

		$query->addSelect(Query::expr()->count('ID'), 'TOTAL_COUNT');
		$query->where('STAGE_SEMANTIC_ID', PhaseSemantics::SUCCESS);

		$minDeals = $requestParameters['DEALS_from'];
		$maxDeals = $requestParameters['DEALS_to'];

		if($minDeals == $maxDeals)
		{
			$query->where(Query::expr()->count('ID'), '=', $minDeals);
		}
		else
		{
			$query->where(Query::expr()->count('ID'), '>=', $minDeals);
			if ($maxDeals > 0)
			{
				$query->where(Query::expr()->count('ID'), '<=', $maxDeals);
			}
		}

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);

		return [
			'__JOINS' => [
				[
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') REP ON REP.OWNER_ID = L.ID'
				]
			]
		];
	}

	public static function getGroupLabel(int $min, int $max)
	{
		if ($min == $max)
		{
			$suffixes = [5, 1, 2, 2, 2, 5];
			$count = $min;
			$suffix = ($count % 100 > 4 && $count % 100 < 20) ? 5 : $suffixes[min($count % 10, 5)];
			return Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_DEALS_COUNT_EXACT_" . $suffix, [
				"#COUNT#" => $count
			]);
		}
		else if ($max == 0)
		{
			return Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_DEAL_COUNT_MORE", [
				"#COUNT#" => $min
			]);
		}
		else
		{
			return Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_DEAL_COUNT_BETWEEN", [
				"#COUNT_FROM#" => $min,
				"#COUNT_TO#" => $max,
			]);
		}
	}

	public function getMultipleGroupedDemoData()
	{
		return [];
	}
}
