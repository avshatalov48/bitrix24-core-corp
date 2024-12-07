<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\DealTable;
use CCrmOwnerType;

class DealProvider extends EntityProvider
{
	protected static DealTable|string $dataClass = DealTable::class;

	protected function getEntityTypeId(): int
	{
		return CCrmOwnerType::Deal;
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
		return '/bitrix/images/crm/entity_provider_icons/deal.svg';
	}
}
