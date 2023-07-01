<?php

namespace Bitrix\Crm\Search\Result\Provider\IndexSupported;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Crm\Search\Result;

class ContactProvider extends \Bitrix\Crm\Search\Result\Provider\IndexSupportedProvider
{
	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	protected function areRequisitesSupported(): bool
	{
		return true;
	}

	protected function getIndexTableQuery(): Query
	{
		return \Bitrix\Crm\Entity\Index\ContactTable::query();
	}

	protected function getEntityTableQuery(): Query
	{
		return \Bitrix\Crm\ContactTable::query();
	}

	protected function getPermissionEntityTypes(): array
	{
		return $this->getPermissionEntityTypesByAffectedCategories();
	}

	protected function getShortIndexColumnName(): string
	{
		return 'CONTACT_ID';
	}


	protected function searchByDenomination(string $searchQuery): Result
	{
		$result = new Result();

		$parts = preg_split('/[\s]+/', $searchQuery, 2, PREG_SPLIT_NO_EMPTY);
		if (count($parts) < 2)
		{
			$filter = ['%FULL_NAME' => $searchQuery];
		}
		else
		{
			$filter = ['LOGIC' => 'AND'];
			for ($i = 0; $i < 2; $i++)
			{
				$filter["__INNER_FILTER_NAME_{$i}"] = ['%FULL_NAME' => $parts[$i]];
			}
		}

		if (!empty($this->additionalFilter))
		{
			$filter = array_merge($filter, $this->additionalFilter);
		}

		$contacts = \CCrmContact::GetListEx(
			[],
			$filter,
			false,
			['nTopCount' => $this->limit],
			['ID']
		);

		while ($contact = $contacts->Fetch())
		{
			$result->addId($contact['ID']);
		}

		return $result;
	}
}
