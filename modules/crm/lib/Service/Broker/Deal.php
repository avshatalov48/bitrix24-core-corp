<?php


namespace Bitrix\Crm\Service\Broker;


use Bitrix\Crm\DealTable;
use Bitrix\Crm\Service\Broker;

class Deal extends Broker
{
	protected function loadEntry(int $id)
	{
		return DealTable::getById($id)->fetchObject();
	}

	protected function loadEntries(array $ids): array
	{
		$dealCollection = DealTable::getList([
			'filter' => [
				'@ID' => $ids,
			],
		])->fetchCollection();

		$deals = [];
		foreach ($dealCollection as $deal)
		{
			$deals[$deal->getId()] = $deal;
		}

		return $deals;
	}
}
