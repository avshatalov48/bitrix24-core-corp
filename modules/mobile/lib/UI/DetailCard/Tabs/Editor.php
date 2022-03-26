<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\UI\DetailCard\Tabs;

use Bitrix\Main\Localization\Loc;

class Editor extends Base
{
	protected const TYPE = 'editor';

	protected function getDefaultTitle(): string
	{
		return Loc::getMessage('M_UI_TAB_EDITOR_DEFAULT_TITLE');
	}
}