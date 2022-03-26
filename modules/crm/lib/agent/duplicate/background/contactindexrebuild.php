<?php
namespace Bitrix\Crm\Agent\Duplicate\Background;

use CCrmOwnerType;

class ContactIndexRebuild extends IndexRebuild
{
	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Contact;
	}
}
