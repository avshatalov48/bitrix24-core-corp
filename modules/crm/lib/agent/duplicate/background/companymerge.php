<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use CCrmOwnerType;

class CompanyMerge extends Merge
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Company;
	}
}
