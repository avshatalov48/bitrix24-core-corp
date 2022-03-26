<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use CCrmOwnerType;

class ContactMerge extends Merge
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Contact;
	}
}
