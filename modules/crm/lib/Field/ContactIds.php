<?php

namespace Bitrix\Crm\Field;

class ContactIds extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}
}
