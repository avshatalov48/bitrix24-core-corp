<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature\Category\Recurring;
use Bitrix\Main\Localization\Loc;

class RecurringSmartInvoice extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('CRM_FEATURE_RECURRING_SMART_INVOICE_NAME');
	}

	protected function getOptionName(): string
	{
		return 'CRM_RECURRING_SMART_INVOICE';
	}

	public function getCategory(): Recurring
	{
		return Recurring::getInstance();
	}
}
