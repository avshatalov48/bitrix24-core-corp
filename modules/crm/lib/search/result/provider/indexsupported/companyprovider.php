<?php

namespace Bitrix\Crm\Search\Result\Provider\IndexSupported;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Search\Result;

class CompanyProvider extends \Bitrix\Crm\Search\Result\Provider\IndexSupportedProvider
{
	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	protected function areRequisitesSupported(): bool
	{
		return true;
	}

	protected function getIndexTableQuery(): Query
	{
		return \Bitrix\Crm\Entity\Index\CompanyTable::query();
	}

	protected function getEntityTableQuery(): Query
	{
		return \Bitrix\Crm\CompanyTable::query();
	}

	protected function getPermissionEntityTypes(): array
	{
		return $this->getPermissionEntityTypesByAffectedCategories();
	}

	protected function getShortIndexColumnName(): string
	{
		return 'COMPANY_ID';
	}

	protected function searchByDenomination(string $searchQuery): Result
	{
		$result = new Result();

		$filter = ['%TITLE' => $searchQuery];
		if (!empty($this->additionalFilter))
		{
			$filter = array_merge($filter, $this->additionalFilter);
		}

		$companies = \CCrmCompany::GetListEx(
			[],
			$filter,
			false,
			[
				'nTopCount' => $this->limit,
			],
			['ID']
		);

		while ($company = $companies->Fetch())
		{
			$result->addId($company['ID']);
		}

		return $result;
	}
}
