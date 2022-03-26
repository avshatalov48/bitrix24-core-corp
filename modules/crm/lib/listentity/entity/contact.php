<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Contact extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::ContactName;
	}
}
