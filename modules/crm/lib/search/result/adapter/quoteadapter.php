<?php

namespace Bitrix\Crm\Search\Result\Adapter;

class QuoteAdapter extends \Bitrix\Crm\Search\Result\Adapter
{
	protected function loadItemsByIds(array $ids): array
	{
		$result = [];
		$quotes = \CCrmQuote::GetList(
			[],
			['@ID' => $ids, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'BEGINDATE', 'QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME']
		);
		while ($quote = $quotes->Fetch())
		{
			$result[] = $quote;
		}

		return $result;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
	}

	protected function prepareTitle(array $item): string
	{
		return $item['TITLE'] ?? \Bitrix\Crm\Item\Quote::getTitlePlaceholderFromData($item);
	}

	protected function prepareSubTitle(array $item): string
	{
		$descriptions = [];
		if (isset($item['COMPANY_TITLE']) && $item['COMPANY_TITLE'] != '')
		{
			$descriptions[] = $item['COMPANY_TITLE'];
		}
		$descriptions[] = \CCrmContact::PrepareFormattedName(
			[
				'LOGIN' => '',
				'HONORIFIC' => $item['CONTACT_HONORIFIC'] ?? '',
				'NAME' => $item['CONTACT_NAME'] ?? '',
				'SECOND_NAME' => $item['CONTACT_SECOND_NAME'] ?? '',
				'LAST_NAME' => $item['CONTACT_LAST_NAME'] ?? '',
			]
		);

		return implode(', ', $descriptions);
	}

	protected function areMultifieldsSupported(): bool
	{
		return false;
	}
}
