<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Contact extends Base
{
	protected function loadValue(string $fieldId): void
	{
		if ($fieldId === 'COMPANY_IDS')
		{
			$this->document['COMPANY_IDS'] = Crm\Binding\ContactCompanyTable::getContactCompanyIDs($this->id);
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

		$result = \CCrmContact::GetListEx(
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

		$this->appendDefaultUserPrefixes();

		$this->loadAddressValues();
		$this->loadFmValues();
		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->loadUserFieldValues();

		$this->document = Crm\Entity\CommentsHelper::prepareFieldsFromBizProc($this->typeId, $this->id, $this->document);
	}

	protected function loadAddressValues(): void
	{
		parent::loadAddressValues();

		$this->document['FULL_ADDRESS'] = Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma(
			Crm\ContactAddress::mapEntityFields($this->document)
		);
	}
}
