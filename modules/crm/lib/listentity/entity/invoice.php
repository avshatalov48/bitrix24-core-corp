<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Invoice extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::InvoiceName;
	}
}
