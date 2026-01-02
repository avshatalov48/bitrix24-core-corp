<?php

namespace Bitrix\Crm\Field;

class CompanyIds extends AbstractRelatedEntityField
{
	protected function getRelatedEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}
}
