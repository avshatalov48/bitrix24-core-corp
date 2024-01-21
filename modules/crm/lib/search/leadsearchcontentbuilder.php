<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\Entity\Index;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\LeadTable;
use CCrmCurrency;
use CCrmLead;
use CCrmOwnerType;

final class LeadSearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmLead::class;
	protected string $entityIndexTableClassName = Index\LeadTable::class;
	protected string $entityIndexTable = 'b_crm_lead_index';
	protected string $entityIndexTablePrimaryColumn = 'LEAD_ID';

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Lead;
	}

	public function convertEntityFilterValues(array &$filter): void
	{
		$this->transferEntityFilterKeys(['FIND', 'PHONE'], $filter);
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
			CCrmLead::GetDefaultTitleTemplate(),
			$map
		);

		$map->addField($fields, 'LAST_NAME');
		$map->addField($fields, 'NAME');
		$map->addField($fields, 'SECOND_NAME');

		if (!$isShortIndex)
		{
			$map->addField($fields, 'COMPANY_TITLE');
			$map->addField($fields, 'OPPORTUNITY');
			$map->add(CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID'] ?? ''));

			if (isset($fields['ASSIGNED_BY_ID']))
			{
				$map->addUserByID($fields['ASSIGNED_BY_ID']);
			}
		}

		$map = $this->addPhonesAndEmailsToSearchMap($entityId, $map);

		if (!$isShortIndex)
		{
			//region Status
			if (isset($fields['STATUS_ID']))
			{
				$map->addStatus('STATUS', $fields['STATUS_ID']);
			}

			if (isset($fields['STATUS_DESCRIPTION']))
			{
				$map->addText($fields['STATUS_DESCRIPTION'], 1024);
			}
			//endregion

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

			//region Address
            $address = preg_replace(
                '/[,.]/', '',
                AddressFormatter::getSingleInstance()->formatTextComma(
                    LeadAddress::mapEntityFields($fields)
                )
            );

			if ($address !== '')
			{
				$map->add($address);
			}
			//endregion

			if (isset($fields['COMMENTS']))
			{
				$map->addHtml($fields['COMMENTS'], 1024);
			}

			if (isset($fields['IS_RETURN_CUSTOMER']) && $fields['IS_RETURN_CUSTOMER'] === 'Y')
			{
				$map->add(CCrmLead::GetFieldCaption('IS_RETURN_CUSTOMER'));
			}

			//region UserFields
			$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
			foreach($userFields as $userField)
			{
				$map->addUserField($userField);
			}
			//endregion
		}

		return $map;
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		LeadTable::update(
			$entityId,
			$this->prepareUpdateData(LeadTable::getTableName(), $map->getString())
		);
	}
}
