<?php

namespace Bitrix\Crm\Field;

class LeadId extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}
}
