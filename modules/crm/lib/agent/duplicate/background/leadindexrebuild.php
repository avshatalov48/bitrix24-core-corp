<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use CCrmOwnerType;

class LeadIndexRebuild extends IndexRebuild
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Lead;
	}
}
