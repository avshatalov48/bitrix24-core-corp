<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Item;
use Bitrix\Main\Localization\Loc;

class Deal extends Item
{
	public const FIELD_NAME_IS_NEW = 'IS_NEW';
	public const FIELD_NAME_IS_REPEATED_APPROACH = 'IS_REPEATED_APPROACH';
	public const FIELD_NAME_PROBABILITY = 'PROBABILITY';

	protected function compilePrimaryForBinding(array $contactBinding): array
	{
		return [
			'DEAL_ID' => $this->getId(),
			'CONTACT_ID' => EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $contactBinding),
		];
	}

	protected function getContactBindingTableClassName(): string
	{
		return DealContactTable::class;
	}

	protected function getItemReferenceFieldNameInProduct(): string
	{
		return 'DEAL_OWNER';
	}

	protected function disableCheckUserFields(): void
	{
		DealTable::disableUserFieldsCheck();
	}

	public function getTitlePlaceholder(): ?string
	{
		return Loc::getMessage('CRM_DEAL_DEFAULT_TITLE_TEMPLATE', array('%NUMBER%' => $this->getId()));
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
