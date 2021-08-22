<?php

namespace Bitrix\Crm\Search\Result\Provider\IndexSupported;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Search\Result;

class LeadProvider extends \Bitrix\Crm\Search\Result\Provider\IndexSupportedProvider
{
	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}

	protected function areRequisitesSupported(): bool
	{
		return false;
	}

	protected function getIndexTableQuery(): Query
	{
		return \Bitrix\Crm\Entity\Index\LeadTable::query();
	}

	protected function getEntityTableQuery(): Query
	{
		return \Bitrix\Crm\LeadTable::query();
	}

	protected function getPermissionEntityTypes(): array
	{
		return [
			\CCrmOwnerType::LeadName,
		];
	}

	protected function getShortIndexColumnName(): string
	{
		return 'LEAD_ID';
	}

	protected function searchByDenomination(string $searchQuery): Result
	{
		$result = new Result();

		$filter = [
			'LOGIC' => 'OR',
			'%FULL_NAME' => $searchQuery,
			'%TITLE' => $searchQuery,
		];

		if (!empty($this->additionalFilter))
		{
			$filter = array_merge($filter, $this->additionalFilter);
		}

		$leads = \CCrmLead::GetListEx(
			[],
			$filter,
			false,
			['nTopCount' => $this->limit],
			['ID']
		);

		while ($lead = $leads->Fetch())
		{
			$result->addId($lead['ID']);
		}

		return $result;
	}
}
