<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Item;

class ContactId extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	protected function getMultipleImplementationFieldName(): ?string
	{
		return Item::FIELD_NAME_CONTACT_IDS;
	}
}
