<?php
/**
 * @var CUser $USER
 * @var CMain $APPLICATION
 * @var array $arResult
 */

use Bitrix\Intranet\Integration\Im\ChatProvider;
use Bitrix\Main;
use \Bitrix\Intranet\UI\LeftMenu;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Main\Localization\Loc::loadMessages(__FILE__);

$userId = \Bitrix\Intranet\CurrentUser::get()->getId();
$defaultItems = $arResult;
$menuUser = new LeftMenu\User();
$menu = new LeftMenu\Menu($defaultItems, $menuUser);
$isCollaber = Loader::includeModule('extranet')
	&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById($userId);
$activePreset = LeftMenu\Preset\Manager::getPreset($isCollaber ? 'collab' : null);
$menu->applyPreset($activePreset);
$visibleItems = $menu->getVisibleItems();

$openItems = [];
$hiddenItems = [];
$isOpen = true;
foreach ($visibleItems as $item)
{
	if (isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y')
	{
		continue;
	}

	if ($isOpen)
	{
		$openItems[] = $item;

		if (count($openItems) === 16)
		{
			$isOpen = false;
		}
	}
	else
	{
		$hiddenItems[] = $item;
	}
}

$arResult = [
	'ITEMS' => [
		'open' => $openItems,
		'hidden' => $hiddenItems,
	]
];

$counters = \CUserCounter::GetValues($USER->GetID(), SITE_ID);
$counters = is_array($counters) ? $counters : [];

$chatProvider = new ChatProvider();
if ($chatProvider->isAvailable())
{
	$counters['im-message'] = $chatProvider->getUnreadMessagesCountForUser($userId);
}

$arResult["COUNTERS"] = $counters;