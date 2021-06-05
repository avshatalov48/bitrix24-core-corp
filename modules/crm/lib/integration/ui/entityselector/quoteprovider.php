<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\QuoteTable;

class QuoteProvider extends EntityProvider
{
	/** @var QuoteTable */
	protected static $dataClass = QuoteTable::class;

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
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
