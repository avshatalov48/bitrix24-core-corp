<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\EO_Lead;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\Service\Broker;

/**
 * @method EO_Lead|null getById(int $id)
 * @method EO_Lead[] getBunchByIds(array $ids)
 */
class Lead extends Broker
{
	protected function loadEntry(int $id)
	{
		return LeadTable::getById($id)->fetchObject();
	}

	protected function loadEntries(array $ids): array
	{
		$leadCollection = LeadTable::getList([
			'filter' => [
				'@ID' => $ids,
			],
		])->fetchCollection();

		$leads = [];
		foreach ($leadCollection as $lead)
		{
			$leads[$lead->getId()] = $lead;
		}

		return $leads;
	}
}
