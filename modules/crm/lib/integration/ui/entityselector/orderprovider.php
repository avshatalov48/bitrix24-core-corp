<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Order\Order;

class OrderProvider extends EntityProvider
{
	/** @var Order */
	protected static $dataClass = Order::class;

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Order;
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
