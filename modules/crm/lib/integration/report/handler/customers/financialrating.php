<?php


namespace Bitrix\Crm\Integration\Report\Handler\Customers;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\DealTable;

abstract class FinancialRating extends Deal
{
	protected const LIMIT = 25;

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();;
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?: 0;
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId))
		{
			return false;
		}

		$mainQuery = $this->prepareQuery();
		$mainQueryResult = $mainQuery->exec()->fetchAll();

		$contacts = [];
		$companies = [];

		$totalDeals = [];
		$previousPeriodAmounts = [];

		foreach ($mainQueryResult as $row)
		{
			if($row['OWNER_TYPE'] === \CCrmOwnerType::ContactName)
			{
				$contacts[] = (int)$row['OWNER_ID'];
			}
			else
			{
				$companies[] = (int)$row['OWNER_ID'];
			}
		}

		if (!empty($contacts))
		{
			$contactDealsQuery = $this->prepareTotalCountQuery(\CCrmOwnerType::ContactName, $contacts);
			foreach ($contactDealsQuery->exec()->getIterator() as $row)
			{
				$key = \CCrmOwnerType::ContactName . "_" .$row['OWNER_ID'];
				$totalDeals[$key] = $row;
			}

			$contactPrevAmountsQuery = $this->preparePreviousWonAmountQuery(\CCrmOwnerType::ContactName, $contacts);
			foreach ($contactPrevAmountsQuery->exec()->getIterator() as $row)
			{
				$key = \CCrmOwnerType::ContactName . "_" .$row['OWNER_ID'];
				$previousPeriodAmounts[$key] = $row;
			}
		}

		if (!empty($companies))
		{
			$companyDealsQuery = $this->prepareTotalCountQuery(\CCrmOwnerType::CompanyName, $companies);
			foreach ($companyDealsQuery->exec()->getIterator() as $row)
			{
				$key = \CCrmOwnerType::CompanyName . "_" .$row['OWNER_ID'];
				$totalDeals[$key] = $row;
			}

			$companyPrevAmountsQuery = $this->preparePreviousWonAmountQuery(\CCrmOwnerType::CompanyName, $companies);
			foreach ($companyPrevAmountsQuery->exec()->getIterator() as $row)
			{
				$key = \CCrmOwnerType::CompanyName . "_" .$row['OWNER_ID'];
				$previousPeriodAmounts[$key] = $row;
			}
		}

		$result = [];
		foreach ($mainQueryResult as $k => $row)
		{
			$key = $row['OWNER_TYPE'] . "_" .$row['OWNER_ID'];
			$result[] = [
				'OWNER_TYPE' => $row['OWNER_TYPE'],
				'OWNER_ID' => $row['OWNER_ID'],
				'WON_AMOUNT' => $row['TOTAL_AMOUNT'],
				'WON_COUNT' => $row['DEAL_COUNT'],
				'TOTAL_COUNT' => (int)($totalDeals[$key]['TOTAL_DEAL_COUNT'] ?? 0),
				'PREV_WON_COUNT' => (int)($previousPeriodAmounts[$key]['SUCCESS_COUNT'] ?? 0),
				'PREV_WON_AMOUNT' => (double)($previousPeriodAmounts[$key]['SUCCESS_AMOUNT'] ?? 0),
			];
		}

		return $result;
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

	protected function addPreviousTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = new DateTime($timePeriodValue['to']);
			$fromDateValue = new DateTime($timePeriodValue['from']);

			[$newFrom, $newTo] = static::getPreviousPeriod($fromDateValue, $toDateValue);

			$query->whereBetween("MOVED_TIME", $newFrom, $newTo);
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
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->addSelect(Query::expr()->sum("OPPORTUNITY_ACCOUNT"), "TOTAL_AMOUNT")
			->whereNotNull("CONTACT.ID")
			->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS)
			->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'));

		$query2
			->addSelect(new \Bitrix\Main\Entity\ExpressionField("OWNER_TYPE", "'COMPANY'"))
			->addSelect("COMPANY.ID", "OWNER_ID")
			->addSelect(Query::expr()->count("ID"), "DEAL_COUNT")
			->addSelect(Query::expr()->sum("OPPORTUNITY_ACCOUNT"), "TOTAL_AMOUNT")
			->whereNotNull("COMPANY.ID")
			->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);

		$query1->unionAll($query2);
		$query1->addUnionOrder("TOTAL_AMOUNT", "DESC");
		$query1->setUnionLimit(static::LIMIT);

		return $query1;
	}

	public function prepareTotalCountQuery($entityType, array $entityId)
	{
		$filterParameters = $this->getFilterParameters();
		$query = DealTable::query();
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addPermissionsCheck($query);

		if ($filterParameters['TIME_PERIOD']['from'] !== "" && $filterParameters['TIME_PERIOD']['to'] !== "")
		{
			$toDateValue = new DateTime($filterParameters['TIME_PERIOD']['to']);
			$fromDateValue = new DateTime($filterParameters['TIME_PERIOD']['from']);

			$query->where("DATE_CREATE", '<=', $toDateValue);
			$query->whereBetween("MOVED_TIME", $fromDateValue, $toDateValue);
		}

		$query->addSelect(Query::expr()->count("ID"), "TOTAL_DEAL_COUNT");

		if ($entityType === \CCrmOwnerType::ContactName)
		{
			$query->addSelect("CONTACT.ID", "OWNER_ID");
			$query->whereIn('CONTACT_ID', $entityId);
			$query->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'));
		}
		else if ($entityType === \CCrmOwnerType::CompanyName)
		{
			$query->addSelect("COMPANY.ID", "OWNER_ID");
			$query->whereIn('COMPANY_ID', $entityId);
		}
		else
		{
			throw new SystemException("Unknown entity type " . $entityType);
		}

		return $query;
	}

	public function preparePreviousWonAmountQuery($entityType, array $entityId)
	{
		$filterParameters = $this->getFilterParameters();
		$query = DealTable::query();
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addPreviousTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		$this->addPermissionsCheck($query);

		$query->addSelect(Query::expr()->sum("OPPORTUNITY_ACCOUNT"), "SUCCESS_AMOUNT");
		$query->addSelect(Query::expr()->count("ID"), "SUCCESS_COUNT");
		$query->where("STAGE_SEMANTIC_ID", PhaseSemantics::SUCCESS);

		if ($entityType === \CCrmOwnerType::ContactName)
		{
			$query->addSelect("CONTACT.ID", "OWNER_ID");
			$query->whereIn('CONTACT_ID', $entityId);
			$query->where(Query::filter()->logic('or')->where('COMPANY_ID', 0)->whereNull('COMPANY_ID'));
		}
		else if ($entityType === \CCrmOwnerType::CompanyName)
		{
			$query->addSelect("COMPANY.ID", "OWNER_ID");
			$query->whereIn('COMPANY_ID', $entityId);
		}
		else
		{
			throw new SystemException("Unknown entity type " . $entityType);
		}

		return $query;
	}
}
