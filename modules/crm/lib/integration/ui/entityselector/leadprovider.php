<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\LeadTable;

class LeadProvider extends EntityProvider
{
	/** @var LeadTable */
	protected static $dataClass = LeadTable::class;

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}

	protected function fetchEntryIds(array $filter): array
	{
		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => $filter,
		])->fetchCollection();

		return $collection->getIdList();
	}
}