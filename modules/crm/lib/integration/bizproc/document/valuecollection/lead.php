<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Lead extends Base
{
	protected function loadValue(string $fieldId): void
	{
		if ($fieldId === 'CONTACT_IDS')
		{
			$this->document['CONTACT_IDS'] = Crm\Binding\LeadContactTable::getLeadContactIDs($this->id);
		}
		elseif ($fieldId === 'COMPANY_TITLE')
		{
			$this->loadCompanyTitle();
		}
		else
		{
			$this->loadEntityValues();
		}
	}

	protected function loadEntityValues(): void
	{
		if (isset($this->document['ID']))
		{
			return;
		}

		$result = \CCrmLead::GetListEx(
			[],
			[
				'ID' => $this->id,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['*']
		);


		$this->document = array_merge($this->document, $result->fetch() ?: []);

		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->appendDefaultUserPrefixes();
		$this->appendCustomerFields();

		if ($this->document['COMPANY_ID'] > 0)
		{
			unset($this->document['COMPANY_TITLE']);
		}

		$this->document['FULL_ADDRESS'] = Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma(
			Crm\LeadAddress::mapEntityFields($this->document)
		);

		$statuses = \CCrmStatus::GetStatusList('STATUS');
		$statusId = $this->document['STATUS_ID'] ?? '';
		$this->document['STATUS_ID_PRINTABLE'] = $statusId && isset($statuses[$statusId]) ? $statuses[$statusId] : '';

		$this->loadFmValues();
		$this->loadUserFieldValues();

		$this->document = Crm\Entity\CommentsHelper::prepareFieldsFromBizProc($this->typeId, $this->id, $this->document);
	}

	protected function loadCompanyTitle(): void
	{
		$this->loadEntityValues();
		if ($this->document['COMPANY_ID'] > 0 && empty($this->document['COMPANY_TITLE']))
		{
			$listResult = \CCrmCompany::GetListEx(
				[],
				[
					'=ID' => $this->document['COMPANY_ID'],
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				false,
				['TITLE']
			);
			$row = $listResult ? $listResult->fetch() : null;
			$this->document['COMPANY_TITLE'] = $row ? $row['TITLE'] : '';
		}
	}

	protected function appendCustomerFields(): void
	{
		if (\CCrmLead::ResolveCustomerType($this->document) === Crm\CustomerType::RETURNING)
		{
			$customerFields = \CCrmLead::getCustomerFields();
			if ($this->document['CONTACT_ID'] > 0)
			{
				if ($contact = \CCrmContact::GetByID($this->document['CONTACT_ID'], false))
				{
					foreach ($customerFields as $customerField)
					{
						if (array_key_exists($customerField, $this->document) && !empty($contact[$customerField]))
						{
							$this->document[$customerField] = $contact[$customerField];
						}
					}
				}
			}
		}
	}
}
