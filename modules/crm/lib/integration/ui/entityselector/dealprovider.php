<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\DealTable;

class DealProvider extends EntityProvider
{
	/** @var DealTable */
	protected static $dataClass = DealTable::class;

	public function isAvailable(): bool
	{
		return \CCrmDeal::CheckReadPermission();
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
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