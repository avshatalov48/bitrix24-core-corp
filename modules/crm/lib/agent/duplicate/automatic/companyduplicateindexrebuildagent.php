<?php
namespace Bitrix\Crm\Agent\Duplicate\Automatic;

class CompanyDuplicateIndexRebuildAgent extends EntityDuplicateIndexRebuildAgent
{

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

}