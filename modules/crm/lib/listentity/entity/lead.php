<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Lead extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::LeadName;
	}
}
