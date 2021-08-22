<?php

namespace Bitrix\Crm\Search\Result\Adapter;

class ContactAdapter extends \Bitrix\Crm\Search\Result\Adapter
{
	protected function loadItemsByIds(array $ids): array
	{
		$result = [];
		$contacts = \CCrmContact::GetListEx(
			[],
			['@ID' => $ids, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'ORIGINATOR_ID']
		);
		while ($contact = $contacts->Fetch())
		{
			$result[] = $contact;
		}

		return $result;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	protected function prepareTitle(array $item): string
	{
		return \CCrmContact::PrepareFormattedName($item);
	}

	protected function prepareSubTitle(array $item): string
	{
		return '';
	}

	protected function areMultifieldsSupported(): bool
	{
		return true;
	}
}
