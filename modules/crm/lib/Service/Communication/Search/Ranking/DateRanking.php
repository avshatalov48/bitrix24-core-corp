<?php

namespace Bitrix\Crm\Service\Communication\Search\Ranking;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ORM\Query\Query;
use CCrmOwnerType;

abstract class DateRanking extends BaseRanking
{
	public function rank(int $rankedEntityTypeId): array
	{
		if ($rankedEntityTypeId === CCrmOwnerType::Lead)
		{
			return $this->rankByLeads();
		}

		if ($rankedEntityTypeId === CCrmOwnerType::Deal)
		{
			return $this->rankByDeals();
		}

		if ($rankedEntityTypeId === CCrmOwnerType::Contact)
		{
			return $this->rankByContacts();
		}

		if ($rankedEntityTypeId === CCrmOwnerType::Company)
		{
			return $this->rankByCompanies();
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($rankedEntityTypeId))
		{
			return $this->rankByDynamics($rankedEntityTypeId);
		}

		return [];
	}

	protected function rankByLeads(): array
	{
		$params = [
			'returnCustomerLead' => !in_array(\CCrmOwnerType::Lead, $this->searchEntityTypeIds),
		];

		return $this->rankByQuery(
			$this->getQueryBuilder()->build(\CCrmOwnerType::Lead, $params),
			CCrmOwnerType::Lead
		);
	}

	protected function rankByDeals(): array
	{
		return $this->rankByQuery(
			$this->getQueryBuilder()->build(\CCrmOwnerType::Deal),
			CCrmOwnerType::Deal
		);
	}

	protected function rankByDynamics(int $rankedEntityTypeId): array
	{
		return $this->rankByQuery(
			$this->getQueryBuilder()->build($rankedEntityTypeId),
			$rankedEntityTypeId
		);
	}

	protected function rankByContacts(): array
	{
		return $this->rankByClient(\CCrmOwnerType::Contact);
	}

	protected function rankByCompanies(): array
	{
		return $this->rankByClient(\CCrmOwnerType::Company);
	}

	private function rankByClient(int $entityTypeId): array
	{
		if (empty($this->duplicates[$entityTypeId]))
		{
			return [];
		}

		$query = $this->getQueryBuilder()->build($entityTypeId);

		$item = $query?->exec()->fetch();

		if (!$item)
		{
			return [];
		}

		return [
			'item' => new ItemIdentifier($entityTypeId, $item['MAX_ID']),
		];
	}

	private function rankByQuery(?Query $query, int $rankEntityTypeId): array
	{
		$item = $query?->exec()->fetch();

		if (!$item)
		{
			return [];
		}

		[$contactFieldName] = $this->getContactFieldNames($rankEntityTypeId);
		[$companyFieldName] = $this->getCompanyFieldNames();

		$bindings = [];
		if (!empty($item[$contactFieldName]))
		{
			$bindings[] = new ItemIdentifier(\CCrmOwnerType::Contact, $item[$contactFieldName]);
		}
		if (!empty($item[$companyFieldName]))
		{
			$bindings[] = new ItemIdentifier(\CCrmOwnerType::Company, $item[$companyFieldName]);
		}

		return [
			'item' => new ItemIdentifier($rankEntityTypeId, $item['MAX_ID']),
			'bindings' => $bindings,
		];
	}

	protected function getContactFieldNames(int $rankEntityTypeId): array
	{
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($rankEntityTypeId))
		{
			return ['CONTACTS', 'CONTACT_BINDINGS.CONTACT_ID'];
		}

		return ['BINDING_CONTACT_ID', 'BINDING_CONTACT.CONTACT_ID'];
	}

	protected function getCompanyFieldNames(): array
	{
		return ['COMPANY_ID', 'COMPANY_ID'];
	}

	private function getQueryBuilder(): DateRankingQueryBuilder
	{
		return new DateRankingQueryBuilder(
			$this->searchEntityTypeIds,
			$this->duplicates,
			$this->getOrder()
		);
	}

	abstract protected function getOrder(): array;
}
