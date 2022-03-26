<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Company extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::CompanyName;
	}
}
