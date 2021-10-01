<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;

class UserFieldAddRestriction extends Bitrix24QuantityRestriction
{
	protected $entityTypeId;

	public function __construct()
	{
		$restrictionName = 'crm_user_field_limit';
		$limit = max(0, (int)Bitrix24Manager::getVariable($restrictionName));
		$restrictionSliderInfo = [
			'ID' => 'limit_crm_users_fields',
		];
		parent::__construct($restrictionName, $limit, null, $restrictionSliderInfo);
		$this->load(); // load actual $limit from options
	}

	public function isExceeded(int $entityTypeId): bool
	{
		$limit = $this->getQuantityLimit();
		if ($limit <= 0 || $entityTypeId <= 0)
		{
			return false;
		}
		$count = $this->getCount($entityTypeId);

		return ($count > $limit);
	}

	public function getCount(int $entityTypeId): int
	{
		$ufEntityId = \CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId);
		if ($ufEntityId === '')
		{
			return 0;
		}

		return (int)\Bitrix\Main\UserFieldTable::getCount([
			'=ENTITY_ID' => $ufEntityId,
		]);
	}
}
