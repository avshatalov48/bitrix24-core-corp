<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard\Tabs;

use Bitrix\Main\Localization\Loc;

class Timeline extends Base
{
	protected const TYPE = 'timeline';

	protected function getDefaultTitle(): string
	{
		return Loc::getMessage('M_UI_TAB_TIMELINE_DEFAULT_TITLE2');
	}
}
