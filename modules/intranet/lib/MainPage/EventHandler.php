<?php

namespace Bitrix\Intranet\MainPage;

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\UI\LeftMenu;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;

class EventHandler
{
	public static function onLicenseHasChanged(Event $event): void
	{
		if (
			Loader::includeModule('bitrix24')
			&& $event->getParameter('licenseType')
			&& !Feature::isFeatureEnabledFor('main_page', $event->getParameter('licenseType'))
		)
		{
			(new Publisher)->withdraw();
		}

		ToolsManager::getInstance()->getFirstPageChanger()->changeForAllUsers(LeftMenu\Menu::getDefaultSiteId());
	}
}
