<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use CCrmOwnerType;

class LeadMerge extends Merge
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Lead;
	}
}
