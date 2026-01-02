<?php

namespace Bitrix\SignMobile;

use Bitrix\Mobile\Menu\Manager\MobileMenuManager as BaseMobileMenuManager;

class MobileMenuManager extends BaseMobileMenuManager
{
	public static function onMobileMenuStructureBuilt($menu, $context): array
	{
		return $menu;
	}
}
