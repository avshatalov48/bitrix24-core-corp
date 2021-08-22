<?php

namespace Bitrix\Crm\Search\Result\Adapter;

class DealAdapter extends \Bitrix\Crm\Search\Result\Adapter
{
	protected function loadItemsByIds(array $ids): array
	{
		$result = [];
		$deals = \CCrmDeal::GetListEx(
			[],
			['@ID' => $ids, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME']
		);
		while ($deal = $deals->Fetch())
		{
			$result[] = $deal;
		}

		return $result;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected function prepareTitle(array $item): string
	{
		return $item['TITLE'];
	}

	protected function prepareSubTitle(array $item): string
	{
		$descriptions = [];
		if (isset($deal['COMPANY_TITLE']) && $deal['COMPANY_TITLE'] != '')
		{
			$descriptions[] = $deal['COMPANY_TITLE'];
		}

		$descriptions[] = \CCrmContact::PrepareFormattedName(
			[
				'LOGIN' => '',
				'HONORIFIC' => $deal['CONTACT_HONORIFIC'] ?? '',
				'NAME' => $deal['CONTACT_NAME'] ?? '',
				'SECOND_NAME' => $deal['CONTACT_SECOND_NAME'] ?? '',
				'LAST_NAME' => $deal['CONTACT_LAST_NAME'] ?? '',
			]
		);

		return implode(', ', $descriptions);
	}

	protected function areMultifieldsSupported(): bool
	{
		return false;
	}
}
