<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Activity extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::ActivityName;
	}
}