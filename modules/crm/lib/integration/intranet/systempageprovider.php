<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Integration\Intranet\CustomSection\Page;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSectionPage;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSection;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Web\Uri;

abstract class SystemPageProvider
{
	protected const SEPARATOR = CustomSectionProvider::PAGE_SETTINGS_SEPARATOR;

	/**
	 * returns information about the component that needs to be connected for the system page according to the passed $pageSettings
	 *
	 * @param string $pageSettings
	 * @param Uri $url
	 * @return Component|null
	 */
	public static function getComponent(string $pageSettings, Uri $url): ?Component
	{
		return null;
	}

	/**
	 * Return an instance of itself for the passed $section
	 *
	 * @param CustomSection $section
	 * @return CustomSectionPage|null
	 */
	public static function getPageInstance(CustomSection $section): ?CustomSectionPage
	{
		return null;
	}

	/**
	 * Returns true if $page can be added to the passed $section
	 *
	 * @param CustomSection $section
	 * @return bool
	 */
	public static function isPageAvailable(CustomSection $section): bool
	{
		$pages = $section->getPages();
		if (empty($pages))
		{
			return false;
		}

		return true;
	}
}
