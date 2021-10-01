<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;

class IntegrationShopRestriction extends Bitrix24QuantityRestriction
{
	public function __construct()
	{
		$restrictionName = 'crm_limit_integration_shop_bitrix';
		if (Bitrix24Manager::isFeatureEnabled($restrictionName))
		{
			$limit = max(0, (int)Bitrix24Manager::getVariable($restrictionName));
		}
		else
		{
			$limit = -1;
		}
		$restrictionSliderInfo = [
			'ID' => 'limit_crm_integration_shop_bitrix',
		];
		parent::__construct($restrictionName, $limit, null, $restrictionSliderInfo);
		$this->load();
	}
	public function isExceeded(): bool
	{
		$limit = $this->getQuantityLimit();
		if ($limit < 0)
		{
			return true;
		}
		if ($limit === 0)
		{
			return false;
		}
		$count = $this->getCount();

		return ($count > $limit);
	}
	public function getCount(): int
	{
		return \CCrmExternalSale::GetList([], ['ACTIVE' => 'Y'])->SelectedRowsCount();
	}
}