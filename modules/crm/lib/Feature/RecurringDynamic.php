<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature\Category\Recurring;
use Bitrix\Main\Localization\Loc;

class RecurringDynamic extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('CRM_FEATURE_RECURRING_DYNAMIC_NAME');
	}

	protected function getOptionName(): string
	{
		return 'CRM_RECURRING_DYNAMIC';
	}

	public function getCategory(): Recurring
	{
		return Recurring::getInstance();
	}
}
