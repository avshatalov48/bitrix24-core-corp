<?php
namespace Bitrix\Crm\Agent\Duplicate\Automatic;

class ContactDuplicateIndexRebuildAgent extends EntityDuplicateIndexRebuildAgent
{

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}
}