<?php

namespace Bitrix\Crm\Filter;


use Bitrix\Main\Filter\Settings;

class ActivityFastSearchSettings extends Settings
{
	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Activity;
	}
}
