<?php

namespace Bitrix\Crm\Search\Result\Adapter;

class InvoiceAdapter extends \Bitrix\Crm\Search\Result\Adapter
{
	protected function loadItemsByIds(array $ids): array
	{
		$result = [];
		$invoices = \CCrmInvoice::GetList(
			[],
			['@ID' => $ids, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'ORDER_TOPIC', 'UF_COMPANY_ID', 'UF_CONTACT_ID']
		);
		while ($invoice = $invoices->Fetch())
		{
			$result[] = $invoice;
		}

		return $result;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Invoice;
	}

	protected function prepareTitle(array $item): string
	{
		return $item['ORDER_TOPIC']
			? (string)$item['ORDER_TOPIC']
			: '#' . $item['ID'];
	}

	protected function prepareSubTitle(array $item): string
	{
		$descriptions = [];
		if (isset($item['UF_COMPANY_ID']) && $item['UF_COMPANY_ID'] > 0)
		{
			$companyTitle = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Company, $item['UF_COMPANY_ID']);
			if ($companyTitle !== '')
			{
				$descriptions[] = $companyTitle;
			}
		}

		if (isset($item['UF_CONTACT_ID']) && $item['UF_CONTACT_ID'] > 0)
		{
			$contactName = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Contact, $item['UF_CONTACT_ID']);
			if ($contactName !== '')
			{
				$descriptions[] = $contactName;
			}
		}

		return implode(', ', $descriptions);
	}

	protected function areMultifieldsSupported(): bool
	{
		return false;
	}
}
