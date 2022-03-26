<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard\Tabs;

use Bitrix\Main\Localization\Loc;

class Product extends Base
{
	protected const TYPE = 'product';

	protected function getDefaultTitle(): string
	{
		return Loc::getMessage('M_UI_TAB_PRODUCT_DEFAULT_TITLE');
	}
}
