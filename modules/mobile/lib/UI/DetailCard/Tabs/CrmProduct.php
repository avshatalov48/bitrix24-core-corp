<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard\Tabs;

use Bitrix\Main\Localization\Loc;

class CrmProduct extends Base
{
	protected const TYPE = 'crm-product';

	protected function getDefaultTitle(): string
	{
		return Loc::getMessage('M_UI_TAB_CRM_PRODUCT_DEFAULT_TITLE');
	}
}
