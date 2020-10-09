<?
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult["SHOW_HELP_SPOTLIGHT"] = false;
if (Loader::includeModule('bitrix24') && \CBitrix24::IsPortalAdmin($USER->GetID()))
{
	$arResult["SHOW_HELP_SPOTLIGHT"] = true;
}