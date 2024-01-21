<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Entity\Index;
use CCrmContact;
use CCrmOwnerType;

final class ContactSearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmContact::class;
	protected string $entityIndexTableClassName = Index\ContactTable::class;
	protected string $entityIndexTable = 'b_crm_contact_index';
	protected string $entityIndexTablePrimaryColumn = 'CONTACT_ID';

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Contact;
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
			$fields['LAST_NAME'] ?? '',
			CCrmContact::GetDefaultTitleTemplate(),
			$map
		);
		
		$map->addField($fields, 'NAME');

		if (!$isShortIndex)
		{
			$map->addField($fields, 'SECOND_NAME');
		}

		if (isset($fields['ASSIGNED_BY_ID']) && !$isShortIndex)
		{
			$map->addUserByID($fields['ASSIGNED_BY_ID']);
		}

		$map = $this->addPhonesAndEmailsToSearchMap($entityId, $map);

		if (isset($fields['TYPE_ID']) && !$isShortIndex)
		{
			$map->addStatus('CONTACT_TYPE', $fields['TYPE_ID']);
		}

		if (isset($fields['COMMENTS']) && !$isShortIndex)
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		//region Source
		if (isset($fields['SOURCE_ID']) && !$isShortIndex)
		{
			$map->addStatus('SOURCE', $fields['SOURCE_ID']);
		}

		if (isset($fields['SOURCE_DESCRIPTION']) && !$isShortIndex)
		{
			$map->addText($fields['SOURCE_DESCRIPTION'], 1024);
		}
		//endregion

		//region UserFields
		if (!$isShortIndex)
		{
			$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
			foreach ($userFields as $userField)
			{
				$map->addUserField($userField);
			}
		}
		//endregion

		return $map;
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		ContactTable::update(
			$entityId,
			$this->prepareUpdateData(ContactTable::getTableName(), $map->getString())
		);
	}
}
