<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Settings\CounterSettings;

class ActivityFieldRestriction extends Bitrix24QuantityRestriction
{
	public function __construct()
	{
		$featureName = '';
		$limit = 0;
		$htmlInfo = null;
		$restrictionSliderInfo = [
			'ID' => Bitrix24Manager::isEnterprise()
				? 'limit_crm_activities_max_number'
				: 'limit_crm_50000_activities'
		];

		parent::__construct($featureName, $limit, $htmlInfo, $restrictionSliderInfo);

		$this->load(); // load actual $limit from options
	}

	final public function isExceeded(): bool
	{
		return !CounterSettings::getInstance()->isEnabled();
	}
}
