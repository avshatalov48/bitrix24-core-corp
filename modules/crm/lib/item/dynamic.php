<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\EntityContactTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

class Dynamic extends Item
{
	public function getTitlePlaceholder(): ?string
	{
		return $this->entityObject->getDefaultTitle();
	}

	protected function getContactBindingTableClassName(): string
	{
		return EntityContactTable::class;
	}

	protected function compilePrimaryForBinding(array $contactBinding): array
	{
		return [
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'ENTITY_ID' => $this->getId(),
			'CONTACT_ID' => EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $contactBinding),
		];
	}

	protected function getItemReferenceFieldNameInProduct(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	protected function disableCheckUserFields(): void
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$dataClass = $factory->getDataClass();
			$dataClass::disableUserFieldsCheck();
		}
	}

	protected function getExternalizableFieldNames(): array
	{
		return array_merge(
			parent::getExternalizableFieldNames(),
			[
				$this->getEntityFieldNameByMap(Item::FIELD_NAME_OBSERVERS),
			],
		);
	}
}
