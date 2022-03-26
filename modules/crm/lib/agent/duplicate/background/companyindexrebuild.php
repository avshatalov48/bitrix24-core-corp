<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use CCrmOwnerType;

class CompanyIndexRebuild extends IndexRebuild
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Company;
	}
}
