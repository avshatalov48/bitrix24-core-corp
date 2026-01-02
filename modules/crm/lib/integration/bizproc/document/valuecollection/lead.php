<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Lead extends Base
{
	protected array $contactFields;
	protected array $companyFields;

	protected function processField(string $fieldId): bool
	{
		if ($fieldId === 'CONTACT_IDS')
		{
			$this->document['CONTACT_IDS'] = Crm\Binding\LeadContactTable::getLeadContactIDs($this->id);

			return true;
		}

		if ($fieldId === 'COMPANY_TITLE')
		{
			$this->loadCompanyTitle();

			return true;
		}

		return false;
	}

	protected function loadEntityValues(): void
	{
		if (isset($this->document['ID']))
		{
			return;
		}

		$this->prepareFieldGroups();
		$this->addCustomerField();
		$this->addContactCompanyFields();

		if (!$this->optimizationEnabled || in_array('CONTACT_IDS', $this->select, true))
		{
			$this->document['CONTACT_IDS'] = Crm\Binding\LeadContactTable::getLeadContactIDs($this->id);
		}

		$result = \CCrmLead::GetListEx(
			[],
			[
				'ID' => $this->id,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			$this->select
		);

		$this->document = array_merge($this->document, $result->fetch() ?: []);
		$this->loadAdditionalValues();
		$this->document = Crm\Entity\CommentsHelper::prepareFieldsFromBizProc($this->typeId, $this->id, $this->document);
	}

	protected function loadAdditionalValues(): void
	{
		$this->loadAddressValues();
		$customerFields = \CCrmLead::getCustomerFields();
		if (!empty(array_intersect($this->select, $customerFields)))
		{
			$this->select = array_merge($this->select, ['CONTACT_ID']);
		}

		if (!$this->optimizationEnabled || in_array('COMPANY_TITLE', $this->select, true))
		{
			$this->loadCompanyTitle();
		}

		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->appendDefaultUserPrefixes();

		$hasCustomerFields = !empty(array_intersect($this->select, $customerFields));
		if (!$this->optimizationEnabled || $hasCustomerFields)
		{
			$this->appendCustomerFields();
		}

		if ($this->document['COMPANY_ID'] > 0)
		{
			//unset($this->document['COMPANY_TITLE']);
		}

		$addressFields = Crm\LeadAddress::mapEntityFields($this->document);
		if (!empty(array_filter($addressFields)))
		{
			$this->document['FULL_ADDRESS'] = Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma(
				$addressFields
			);
		}

		if (isset($this->document['STATUS_ID']))
		{
			$statuses = \CCrmStatus::GetStatusList('STATUS');
			$statusId = $this->document['STATUS_ID'] ?? '';
			$this->document['STATUS_ID_PRINTABLE'] = $statusId && isset($statuses[$statusId]) ? $statuses[$statusId] : '';
		}

		$this->loadFmValues();
		$this->loadUserFieldValues();
		$this->loadCommonFieldValues();
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

	protected function addCustomerField(): void
	{
		if (!in_array('IS_RETURN_CUSTOMER', $this->select, true))
		{
			$this->select[] = 'IS_RETURN_CUSTOMER';
		}
	}
}
