<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Item;

class CompanyId extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	protected function getMultipleImplementationFieldName(): ?string
	{
		return Item\Contact::FIELD_NAME_COMPANY_IDS;
	}
}
