<?php

namespace Bitrix\Crm\Item;

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

	/**
	 * @deprecated Real banking details are stored separately now.
	 * This field is not used in the product anymore. It's here for backwards compatibility only.
	 */
	public const FIELD_NAME_BANKING_DETAILS = 'BANKING_DETAILS';

	protected function disableCheckUserFields(): void
	{
		CompanyTable::disableUserFieldsCheck();
	}

	public function getTitlePlaceholder(): ?string
	{
		$number = ($this->getId() > 0) ? $this->getId() : '';

		return Loc::getMessage('CRM_COMPANY_DEFAULT_TITLE_TEMPLATE', ['%NUMBER%' => $number]);
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_diff(parent::getExternalizableFieldNames(), [static::FIELD_NAME_PRODUCTS]);
	}
}
