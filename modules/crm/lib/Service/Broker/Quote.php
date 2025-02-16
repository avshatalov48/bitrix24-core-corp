<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\QuoteTable;
use Bitrix\Crm\Service\Broker;

final class Quote extends Broker
{
	protected ?string $eventEntityAdd = 'OnAfterCrmQuoteAdd';
	protected ?string $eventEntityUpdate = 'OnAfterCrmQuoteUpdate';
	protected ?string $eventEntityDelete = 'OnAfterCrmQuoteDelete';

	protected function loadEntry(int $id)
	{
		return QuoteTable::getById($id)->fetchObject();
	}

	protected function loadEntries(array $ids): array
	{
		$collection = QuoteTable::getList([
			'filter' => [
				'@ID' => $ids,
			],
		])->fetchCollection();

		$quotes = [];
		foreach ($collection as $quote)
		{
			$quotes[$quote->getId()] = $quote;
		}

		return $quotes;
	}
}
