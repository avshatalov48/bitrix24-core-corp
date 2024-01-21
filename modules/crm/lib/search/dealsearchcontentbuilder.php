<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Entity\Index;
use CCrmCurrency;
use CCrmDeal;
use CCrmOwnerType;

final class DealSearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmDeal::class;
	protected string $entityIndexTableClassName = Index\DealTable::class;
	protected string $entityIndexTable = 'b_crm_deal_index';
	protected string $entityIndexTablePrimaryColumn = 'DEAL_ID';

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Deal;
	}
	
	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = (int)($fields['ID'] ?? 0);
		if ($entityId <= 0)
		{
			return $map;
		}

		$isShortIndex = ($options['isShortIndex'] ?? false);
		if (!$isShortIndex)
		{
			$map->add($entityId);
		}

		$map = $this->addTitleToSearchMap(
			$entityId,
			$fields['TITLE'] ?? '',
			CCrmDeal::GetDefaultTitleTemplate(),
			$map
		);

		$map->addField($fields, 'OPPORTUNITY');

		if (!$isShortIndex)
		{
			$map->add(CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID'] ?? ''));

			if (isset($fields['ASSIGNED_BY_ID']))
			{
				$map->addUserByID($fields['ASSIGNED_BY_ID']);
			}
		}

		//region Company
		$companyId = (int)($fields['COMPANY_ID'] ?? 0);
		if ($companyId > 0)
		{
			$map->addCompany($companyId);
		}
		//endregion

		//region Contacts
		$map->addContacts(DealContactTable::getDealContactIDs($entityId));
		//endregion

		if (!$isShortIndex)
		{
			if (isset($fields['TYPE_ID']))
			{
				$map->addStatus('DEAL_TYPE', $fields['TYPE_ID']);
			}

			if (isset($fields['STAGE_ID']))
			{
				$map->add(DealCategory::getStageName($fields['STAGE_ID'], $fields['CATEGORY_ID'] ?? -1));
			}

			if (isset($fields['BEGINDATE']))
			{
				$map->add($fields['BEGINDATE']);
			}

			if (isset($fields['CLOSEDATE']))
			{
				$map->add($fields['CLOSEDATE']);
			}

			if (isset($fields['COMMENTS']))
			{
				$map->addHtml($fields['COMMENTS'], 1024);
			}

			//region Source
			if (isset($fields['SOURCE_ID']))
			{
				$map->addStatus('SOURCE', $fields['SOURCE_ID']);
			}

			if (isset($fields['SOURCE_DESCRIPTION']))
			{
				$map->addText($fields['SOURCE_DESCRIPTION'], 1024);
			}
			//endregion

			//region UserFields
			$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
			foreach ($userFields as $userField)
			{
				$map->addUserField($userField);
			}
			//endregion
		}

		return $map;
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		DealTable::update(
			$entityId,
			$this->prepareUpdateData(DealTable::getTableName(), $map->getString())
		);
	}
}
