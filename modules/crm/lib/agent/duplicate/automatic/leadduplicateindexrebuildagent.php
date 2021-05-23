<?php
namespace Bitrix\Crm\Agent\Duplicate\Automatic;

class LeadDuplicateIndexRebuildAgent extends EntityDuplicateIndexRebuildAgent
{

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}
}