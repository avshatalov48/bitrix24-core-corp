<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\EO_Deal;
use Bitrix\Crm\Service\Broker;

/**
 * @method EO_Deal|null getById(int $id)
 * @method EO_Deal[] getBunchByIds(array $ids)
 */
class Deal extends Broker
{
	protected ?string $eventEntityAdd = 'OnAfterCrmDealAdd';
	protected ?string $eventEntityUpdate = 'OnAfterCrmDealUpdate';
	protected ?string $eventEntityDelete = 'OnAfterCrmDealDelete';

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
