<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if (!isset($arResult['showAll']) || $arResult["showAll"] !== 'Y')
{
	return;
}

$menuItems = [];
$request = Context::getCurrent()->getRequest();
if (!empty($arResult["PATH_TO_MINE"]))
{
	$link = $arResult["PATH_TO_MINE"];
	if ($request->get('IFRAME') === 'Y')
	{
		$uri = new Uri($link);
		$uri->addParams(['IFRAME' => 'Y']);
		$link = $uri->getUri();
	}

	$menuItems[] = [
		'text' => Loc::getMessage('BLOG_MENU_MINE'),
		'title' => Loc::getMessage('BLOG_MENU_MINE_TITLE'),
		'href' => $link,
		'id' => 'view_mine',
		'className' => $arResult['page'] === 'mine' ? 'menu-popup-item-accept' : 'menu-popup-item-none',
	];
}

if ($arResult['show4Me'] === 'Y')
{
	$link = $arResult['PATH_TO_4ME'];
	if ($request->get('IFRAME') === 'Y')
	{
		$uri = new Uri($link);
		$uri->addParams(['IFRAME' => 'Y']);
		$link = $uri->getUri();
	}

	$menuItems[] = [
		'text' => Loc::getMessage('BLOG_MENU_4ME'),
		'title' => Loc::getMessage('BLOG_MENU_4ME_TITLE'),
		'href' => $link,
		'id' => 'view_for_me',
		'className' => $arResult['page'] === 'forme' ? 'menu-popup-item-accept' : 'menu-popup-item-none',
	];
}

if (!empty($arResult['urlToDraft']) && (int)$arResult['CntToDraft'] > 0)
{
	$link = $arResult['urlToDraft'];
	if ($request->get('IFRAME') === 'Y')
	{
		$uri = new Uri($link);
		$uri->addParams(['IFRAME' => 'Y']);
		$link = $uri->getUri();
	}

	$menuItems[] = [
		'text' => Loc::getMessage('BLOG_MENU_DRAFT_MESSAGES'),
		'href' => $link,
		'id' => 'view_draft',
		'className' => $arResult['page'] === 'draft' ? 'menu-popup-item-accept' : 'menu-popup-item-none',
	];
}

if ($arResult["urlToModeration"] <> '' && (int)$arResult["CntToModerate"] > 0)
{
	$link = $arResult['urlToModeration'];
	if ($request->get('IFRAME') === 'Y')
	{
		$uri = new Uri($link);
		$uri->addParams(['IFRAME' => 'Y']);
		$link = $uri->getUri();
	}

	$menuItems[] = [
		'text' => Loc::getMessage('BLOG_MENU_MODERATION_MESSAGES'),
		'href' => $link,
		'id' => 'view_moderation',
		'className' => $arResult['page'] === 'moderation' ? 'menu-popup-item-accept' : 'menu-popup-item-none',
	];
}

if (!empty($arResult["urlToTags"]) && isset($arResult["CntTags"]) && (int)$arResult["CntTags"] > 0)
{
	$link = $arResult['urlToTags'];
	if ($request->get('IFRAME') === 'Y')
	{
		$uri = new Uri($link);
		$uri->addParams(['IFRAME' => 'Y']);
		$link = $uri->getUri();
	}

	$menuItems[] = [
		'text' => Loc::getMessage('BLOG_MENU_TAGS'),
		'href' => $link,
		'className' => $arResult['page'] === 'tags' ? 'menu-popup-item-accept' : 'menu-popup-item-none',
	];
}

if (!empty($menuItems) && ($arResult['show4MeAll'] === 'Y' || $arResult['showAll'] === "Y"))
{
	$link = $arResult['PATH_TO_4ME_ALL'];
	if ($request->get('IFRAME') === 'Y')
	{
		$uri = new Uri($link);
		$uri->addParams(['IFRAME' => 'Y']);
		$link = $uri->getUri();
	}

	array_unshift($menuItems, [
		'text' => Loc::getMessage('BLOG_MENU_4ME_ALL'),
		'title' => Loc::getMessage('BLOG_MENU_4ME_ALL_TITLE'),
		'href' => $link,
		'id' => 'view_4me_all',
		'className' => $arResult['page'] === 'all' ? 'menu-popup-item-accept' : 'menu-popup-item-none',
	]);
}

if (!empty($menuItems))
{
	$buttonText = '';
	foreach ($menuItems as $menuItem)
	{
		if ($menuItem['className'] === 'menu-popup-item-accept')
		{
			$buttonText = $menuItem['text'];
			break;
		}
	}

	$button = new \Bitrix\UI\Buttons\Button([
		'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
		'text' => $buttonText,
  	]);

	$button->setMenu([
		'items' => $menuItems,
		'minWidth' => 150,
	]);

	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($button, \Bitrix\UI\Toolbar\ButtonLocation::AFTER_TITLE);
}
