<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Main\Localization\Loc;

class ShowLinkToFeaturesInMenu extends BaseFeature
{
	public function getSort(): int
	{
		return 1;
	}

	public function getName(): string
	{
		return Loc::getMessage('SHOW_LINK_IN_MENU_PAGE_NAME');
	}

	public static function getMenuTitle():string
	{
		return Loc::getMessage('LINK_IN_MENU_TITLE') ?? 'Extra settings';
	}
}
