<?php

namespace Bitrix\Crm\Search\Result\Adapter;

class LeadAdapter extends \Bitrix\Crm\Search\Result\Adapter
{
	protected function loadItemsByIds(array $ids): array
	{
		$result = [];
		$leads = \CCrmLead::GetListEx(
			[],
			['@ID' => $ids, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
		);
		while ($lead = $leads->Fetch())
		{
			$result[$lead['ID']] = $lead;
		}

		return $result;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}

	protected function prepareTitle(array $item): string
	{
		return $item['TITLE'];
	}

	protected function prepareSubTitle(array $item): string
	{
		return \CCrmLead::PrepareFormattedName($item);
	}

	protected function areMultifieldsSupported(): bool
	{
		return true;
	}
}
