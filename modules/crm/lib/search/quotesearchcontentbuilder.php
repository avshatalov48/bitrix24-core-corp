<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\QuoteTable;
use CCrmCurrency;
use CCrmOwnerType;
use CCrmQuote;

final class QuoteSearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmQuote::class;

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Quote;
	}
	
	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = (int)($fields['ID'] ?? 0);
		if ($entityId <= 0)
		{
			return $map;
		}

		$map->add($entityId);
		$map->addField($fields, 'TITLE');
		$map->addField($fields, 'OPPORTUNITY');
		$map->add(CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID'] ?? ''));

		if (isset($fields['ASSIGNED_BY_ID']))
		{
			$map->addUserByID($fields['ASSIGNED_BY_ID']);
		}

		//region Company
		$companyId = (int)($fields['COMPANY_ID'] ?? 0);
		if ($companyId > 0)
		{
			$map->addCompany($companyId);
		}
		//endregion

		//region Contacts
		$contactIds = QuoteContactTable::getQuoteContactIDs($entityId);
		$map->addContacts($contactIds);
		//endregion

		//region UserFields
		$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
		foreach ($userFields as $userField)
		{
			$map->addUserField($userField);
		}
		//endregion

		return $map;
	}

	protected function prepareForBulkBuild(array $entityIds): void
	{
		$dbResult = CCrmQuote::GetList(
			[],
			['@ID' => $entityIds, 'CHECK_PERMISSIONS' => 'N'],
			['ASSIGNED_BY_ID'],
			false,
			['ASSIGNED_BY_ID']
		);

		$userIds = [];
		while ($fields = $dbResult->Fetch())
		{
			$userIds[] = (int)$fields['ASSIGNED_BY_ID'];
		}

		if (!empty($userIds))
		{
			SearchMap::cacheUsers($userIds);
		}
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		QuoteTable::update(
			$entityId,
			$this->prepareUpdateData(QuoteTable::getTableName(), $map->getString())
		);
	}
}
