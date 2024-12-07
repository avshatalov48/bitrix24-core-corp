<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\LeadTable;
use CCrmOwnerType;

class LeadProvider extends EntityProvider
{
	protected static LeadTable|string $dataClass = LeadTable::class;

	protected function getEntityTypeId(): int
	{
		return CCrmOwnerType::Lead;
	}

	protected function fetchEntryIds(array $filter): array
	{
		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => $filter,
		])->fetchCollection();

		return $collection->getIdList();
	}
	protected function getDefaultItemAvatar(): ?string
	{
		return '/bitrix/images/crm/entity_provider_icons/lead.svg';
	}
}
