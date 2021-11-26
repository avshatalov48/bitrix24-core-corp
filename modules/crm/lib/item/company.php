<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Item;
use Bitrix\Main\Localization\Loc;

class Company extends Item
{
	public const FIELD_NAME_LOGO = 'LOGO';
	public const FIELD_NAME_INDUSTRY = 'INDUSTRY';
	public const FIELD_NAME_EMPLOYEES = 'EMPLOYEES';
	public const FIELD_NAME_REVENUE = 'REVENUE';
	public const FIELD_NAME_IS_MY_COMPANY = 'IS_MY_COMPANY';

	protected function compilePrimaryForBinding(array $contactBinding): array
	{
		return [
			'COMPANY_ID' => $this->getId(),
			'CONTACT_ID' => EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $contactBinding),
		];
	}

	protected function getContactBindingTableClassName(): string
	{
		return ContactCompanyTable::class;
	}

	protected function disableCheckUserFields(): void
	{
		CompanyTable::disableUserFieldsCheck();
	}

	public function getTitlePlaceholder(): ?string
	{
		return Loc::getMessage('CRM_COMPANY_UNTITLED');
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_diff(parent::getExternalizableFieldNames(), [static::FIELD_NAME_PRODUCTS]);
	}
}
