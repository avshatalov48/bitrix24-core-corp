<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Deal extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::DealName;
	}
}
