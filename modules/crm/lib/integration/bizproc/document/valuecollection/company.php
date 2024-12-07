<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Company extends Base
{
	protected function loadValue(string $fieldId): void
	{
		if ($fieldId === 'CONTACT_ID')
		{
			$this->document['CONTACT_ID'] = Crm\Binding\ContactCompanyTable::getCompanyContactIDs($this->id);
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

		$result = \CCrmCompany::GetListEx(
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

		$this->document['ADDRESS'] = Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma(
			Crm\CompanyAddress::mapEntityFields($this->document, ['TYPE_ID' => Crm\EntityAddressType::Delivery])
		);

		$this->document['ADDRESS_LEGAL'] = Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma(
			Crm\CompanyAddress::mapEntityFields($this->document, ['TYPE_ID' => Crm\EntityAddressType::Registered])
		);
	}
}
