<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Item;
use Bitrix\Main\Localization\Loc;

/**
 * @property \Bitrix\Crm\Contact entityObject
 */
class Contact extends Item
{
	public const FIELD_NAME_PHOTO = 'PHOTO';
	public const FIELD_NAME_EXPORT = 'EXPORT';
	public const FIELD_NAME_COMPANY_BINDINGS = 'COMPANY_BINDINGS';
	public const FIELD_NAME_COMPANIES = 'COMPANIES';
	public const FIELD_NAME_COMPANY_IDS = 'COMPANY_IDS';

	protected function disableCheckUserFields(): void
	{
		ContactTable::disableUserFieldsCheck();
	}

	public function getHeading(): ?string
	{
		return $this->entityObject->getFormattedName();
	}

	public function getTitlePlaceholder(): ?string
	{
		$number = ($this->getId() > 0) ? $this->getId() : '';

		return Loc::getMessage('CRM_CONTACT_DEFAULT_TITLE_TEMPLATE', ['%NUMBER%' => $number]);
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_diff(parent::getExternalizableFieldNames(), [
			static::FIELD_NAME_PRODUCTS,
			static::FIELD_NAME_CONTACT_BINDINGS,
			static::FIELD_NAME_CONTACT_IDS,
		]);
	}
}
