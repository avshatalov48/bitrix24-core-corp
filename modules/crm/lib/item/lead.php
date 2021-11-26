<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\LeadTable;
use Bitrix\Main\Localization\Loc;

class Lead extends Item
{
	public const FIELD_NAME_STATUS_ID = 'STATUS_ID';
	public const FIELD_NAME_STATUS_DESCRIPTION = 'STATUS_DESCRIPTION';
	public const FIELD_NAME_CREATED_BY_ID = 'CREATED_BY_ID';
	public const FIELD_NAME_MODIFY_BY_ID = 'MODIFY_BY_ID';
	public const FIELD_NAME_DATE_CREATE = 'DATE_CREATE';
	public const FIELD_NAME_DATE_MODIFY = 'DATE_MODIFY';
	public const FIELD_NAME_DATE_CLOSED = 'DATE_CLOSED';

	protected function compilePrimaryForBinding(array $contactBinding): array
	{
		return [
			'LEAD_ID' => $this->getId(),
			'CONTACT_ID' => EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $contactBinding),
		];
	}

	protected function getContactBindingTableClassName(): string
	{
		return LeadContactTable::class;
	}

	protected function getItemReferenceFieldNameInProduct(): string
	{
		return 'LEAD_OWNER';
	}

	public function getCategoryId(): ?int
	{
		return null;
	}

	protected function disableCheckUserFields(): void
	{
		LeadTable::disableUserFieldsCheck();
	}

	public function getTitlePlaceholder(): ?string
	{
		return Loc::getMessage('CRM_LEAD_DEFAULT_TITLE_TEMPLATE', array('%NUMBER%' => $this->getId()));
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_merge(
			parent::getExternalizableFieldNames(),
			[
				Item::FIELD_NAME_OBSERVERS,
			],
		);
	}
}
