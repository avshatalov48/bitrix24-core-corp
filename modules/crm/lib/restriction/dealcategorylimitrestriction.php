<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;

class DealCategoryLimitRestriction extends Bitrix24QuantityRestriction
{
	public function __construct()
	{
		$limit = max(0, Bitrix24Manager::getDealCategoryCount());

		parent::__construct(
			'crm_clr_cfg_deal_category',
			$limit,
			null,
			[
				'ID' => 'limit_crm_sales_funnels',
			]
		);

		$this->load(); // load actual $limit from options
	}

	public function isExceeded(): bool
	{
		$limit = $this->getQuantityLimit();
		if ($limit <= 0)
		{
			return false;
		}
		$count = $this->getCount();

		return ($count >= $limit);
	}

	public function getCount(): int
	{
		return \Bitrix\Crm\Category\DealCategory::getCount();
	}
}
