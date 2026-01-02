<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Contact extends Base
{
	protected function processField(string $fieldId): bool
	{
		if ($fieldId === 'COMPANY_IDS')
		{
			$this->document['COMPANY_IDS'] = Crm\Binding\ContactCompanyTable::getContactCompanyIDs($this->id);

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
		$this->addContactCompanyFields();

		$result = \CCrmContact::GetListEx(
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
		$this->appendDefaultUserPrefixes();

		if (!$this->optimizationEnabled || in_array('COMPANY_IDS', $this->select, true))
		{
			$this->document['COMPANY_IDS'] = Crm\Binding\ContactCompanyTable::getContactCompanyIDs($this->id);
		}

		$this->loadAddressValues();
		$this->loadFmValues();
		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->loadUserFieldValues();
		$this->loadCommonFieldValues();
	}

	protected function loadAddressValues(): void
	{
		parent::loadAddressValues();

		$addressFields = Crm\ContactAddress::mapEntityFields($this->document);
		if (!empty(array_filter($addressFields)))
		{
			$this->document['FULL_ADDRESS'] = Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma(
				$addressFields
			);
		}
	}
}
