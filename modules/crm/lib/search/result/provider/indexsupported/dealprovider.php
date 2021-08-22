<?php

namespace Bitrix\Crm\Search\Result\Provider\IndexSupported;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Search\Result;

class DealProvider extends \Bitrix\Crm\Search\Result\Provider\IndexSupportedProvider
{
	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected function areRequisitesSupported(): bool
	{
		return false;
	}

	protected function getIndexTableQuery(): Query
	{
		return \Bitrix\Crm\Entity\Index\DealTable::query();
	}

	protected function getEntityTableQuery(): Query
	{
		return \Bitrix\Crm\DealTable::query();
	}

	protected function getPermissionEntityTypes(): array
	{
		return array_merge(
			[
				\CCrmOwnerType::DealName,
			],
			DealCategory::getPermissionEntityTypeList()
		);
	}

	protected function getShortIndexColumnName(): string
	{
		return 'DEAL_ID';
	}

	protected function searchByDenomination(string $searchQuery): Result
	{
		$result = new Result();

		$filter = ['%TITLE' => $searchQuery];
		$deals = \CCrmDeal::GetListEx(
			[],
			$filter,
			false,
			['nTopCount' => $this->limit],
			['ID']
		);

		while ($deal = $deals->Fetch())
		{
			$result->addId($deal['ID']);
		}

		return $result;
	}
}
