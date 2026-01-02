<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Model\Dynamic\RecurringTable;
use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use RuntimeException;

class Recurring extends Broker
{
	private Factory $factory;

	public function __construct(int $entityTypeId)
	{
		parent::__construct();

		$this->factory = Container::getInstance()->getFactory($entityTypeId);
	}

	protected function loadEntry(int $id)
	{
		$this->check();

		return RecurringTable::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->factory->getEntityTypeId(),
				'=ITEM_ID' => $id,
			],
			'limit' => 1,
		])->fetchObject();
	}

	protected function loadEntries(array $ids): array
	{
		$this->check();

		$collection = RecurringTable::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->factory->getEntityTypeId(),
				'@ITEM_ID' => $ids,
			],
		])->fetchCollection();

		$items = [];
		foreach ($collection as $item)
		{
			$items[$item->getId()] = $item;
		}

		return $items;
	}

	private function check(): void
	{
		if (!$this->factory->isRecurringAvailable())
		{
			throw new RuntimeException('Entity type ' . $this->factory->getEntityName() . ' does not support recurring mode');
		}
	}
}
