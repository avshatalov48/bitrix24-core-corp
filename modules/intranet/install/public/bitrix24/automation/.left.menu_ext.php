<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Site\Sections\AutomationSection;
use Bitrix\Main\Loader;
use Bitrix\Intranet\Settings\Tools\ToolsManager;

$GLOBALS['APPLICATION']->setPageProperty('topMenuSectionDir', SITE_DIR . 'automation/');

if (!Loader::includeModule('intranet'))
{
	return;
}

$aMenuLinks = [];
$items = AutomationSection::getItems();
foreach ($items as $item)
{
	if (!empty($item['available']) && ToolsManager::getInstance()->checkAvailabilityByToolId($item['id']))
	{
		$menuData = $item['menuData'] ?? [];
		unset($menuData['counter_id']);

		$aMenuLinks[] = [
			$item['title'] ?? '',
			$item['url'] ?? '',
			$item['extraUrls'] ?? [],
			$menuData,
			'',
		];
	}
}