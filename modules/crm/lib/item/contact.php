<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Item;
use Bitrix\Main\Localization\Loc;

class Contact extends Item
{
	public const FIELD_NAME_PHOTO = 'PHOTO';
	public const FIELD_NAME_EXPORT = 'EXPORT';
	public const FIELD_NAME_COMPANY_IDS = 'COMPANY_IDS';

	protected function compilePrimaryForBinding(array $contactBinding): array
	{
		return [];
	}

	protected function getContactBindingTableClassName(): string
	{
		return '';
	}

	protected function disableCheckUserFields(): void
	{
		ContactTable::disableUserFieldsCheck();
	}

	public function getTitle(): ?string
	{
		return \CCrmContact::PrepareFormattedName([
			'HONORIFIC' => $this->getHonorific(),
			'NAME' => $this->getName(),
			'LAST_NAME' => $this->getLastName(),
			'SECOND_NAME' => $this->getSecondName(),
		]);
	}

	public function getTitlePlaceholder(): ?string
	{
		return Loc::getMessage('CRM_CONTACT_DEFAULT_TITLE_TEMPLATE', array('%NUMBER%' => $this->getId()));
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_diff(parent::getExternalizableFieldNames(), [
			static::FIELD_NAME_PRODUCTS,
			static::FIELD_NAME_CONTACT_BINDINGS,
		]);
	}
}
