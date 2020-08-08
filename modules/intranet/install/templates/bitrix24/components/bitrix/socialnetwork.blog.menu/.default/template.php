<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$request = Context::getCurrent()->getRequest();

if ($arResult["showAll"] == "Y")
{
	$menuItems = array();

	if($arResult["PATH_TO_MINE"] <> '')
	{
		$link = $arResult["PATH_TO_MINE"];
		if ($request->get('IFRAME') == 'Y')
		{
			$uri = new Uri($link);
			$uri->addParams(array("IFRAME" => "Y"));
			$link = $uri->getUri();
		}

		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_MINE"),
			"TITLE" => GetMessage("BLOG_MENU_MINE_TITLE"),
			"URL" => $link,
			"ID" => "view_mine",
			"IS_ACTIVE" => $arResult["page"] == "mine",
		);
	}

	if($arResult["show4Me"] == "Y")
	{
		$link = $arResult["PATH_TO_4ME"];
		if ($request->get('IFRAME') == 'Y')
		{
			$uri = new Uri($link);
			$uri->addParams(array("IFRAME" => "Y"));
			$link = $uri->getUri();
		}

		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_4ME"),
			"TITLE" => GetMessage("BLOG_MENU_4ME_TITLE"),
			"URL" => $link,
			"ID" => "view_for_me",
			"IS_ACTIVE" => $arResult["page"] == "forme",
		);
	}

	if ($arResult["urlToDraft"] <> '' && intval($arResult["CntToDraft"]) > 0)
	{
		$link = $arResult["urlToDraft"];
		if ($request->get('IFRAME') == 'Y')
		{
			$uri = new Uri($link);
			$uri->addParams(array("IFRAME" => "Y"));
			$link = $uri->getUri();
		}

		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_DRAFT_MESSAGES"),
			"URL" => $link,
			"ID" => "view_draft",
			"IS_ACTIVE" => $arResult["page"] == "draft",
		);
	}

	if ($arResult["urlToModeration"] <> '' && intval($arResult["CntToModerate"]) > 0)
	{
		$link = $arResult["urlToModeration"];
		if ($request->get('IFRAME') == 'Y')
		{
			$uri = new Uri($link);
			$uri->addParams(array("IFRAME" => "Y"));
			$link = $uri->getUri();
		}

		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_MODERATION_MESSAGES"),
			"URL" => $link,
			"ID" => "view_moderation",
			"IS_ACTIVE" => $arResult["page"] == "moderation",
		);
	}

	if (
		$arResult["urlToTags"] <> ''
		&& intval($arResult["CntTags"]) > 0
	)
	{
		$link = $arResult["urlToTags"];
		if ($request->get('IFRAME') == 'Y')
		{
			$uri = new Uri($link);
			$uri->addParams(array("IFRAME" => "Y"));
			$link = $uri->getUri();
		}

		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_TAGS"),
			"URL" => $link,
			"ID" => "view_tags",
			"IS_ACTIVE" => $arResult["page"] == "tags",
		);
	}

	if (
		!empty($menuItems)
		&& (
			$arResult["show4MeAll"] == "Y"
			|| $arResult["showAll"] == "Y"
		)
	)
	{
		$link = $arResult["PATH_TO_4ME_ALL"];
		if ($request->get('IFRAME') == 'Y')
		{
			$uri = new Uri($link);
			$uri->addParams(array("IFRAME" => "Y"));
			$link = $uri->getUri();
		}

		array_unshift($menuItems, array(
			"TEXT" => GetMessage("BLOG_MENU_4ME_ALL"),
			"TITLE" => GetMessage("BLOG_MENU_4ME_ALL_TITLE"),
			"URL" => $link,
			"ID" => "view_4me_all",
			"IS_ACTIVE" => $arResult["page"] == "all",
		));
	}

	$this->SetViewTarget("above_pagetitle", 150);

	$menuId = "blog_messages_panel_menu";
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.buttons",
		"",
		array(
			"ID" => $menuId,
			"ITEMS" => $menuItems,
			"DISABLE_SETTINGS" => true
		)
	);

	$this->EndViewTarget();
}