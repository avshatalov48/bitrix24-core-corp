<?
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arResult["PAGE"] = isset($arParams["PAGE"]) && strlen($arParams["PAGE"]) ? $arParams["PAGE"] : "WINDOWS";

if ($arResult["PAGE"] === "MACOS")
{
	$APPLICATION->SetTitle(Loc::getMessage("INTRANET_DISK_PROMO_TITLE_MACOS"));
}
else
{
	$APPLICATION->SetTitle(Loc::getMessage("INTRANET_DISK_PROMO_TITLE_WINDOWS"));
}

$postfix = "";
if (in_array(LANGUAGE_ID, array("ru", "ua")))
{
	$postfix = "_ru";
}
elseif (LANGUAGE_ID === "de")
{
	$postfix = "_de";
}

$arResult["IMAGE_PATH"] =
	$this->getFolder()."/images/".($arResult["PAGE"] === "MACOS" ? "macos" : "windows").$postfix.".png";

$arResult["DOWNLOAD_PATH"] =
	$arResult["PAGE"] === "MACOS" ?
		"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg" :
		"http://dl.bitrix24.com/b24/bitrix24_desktop.exe"
;