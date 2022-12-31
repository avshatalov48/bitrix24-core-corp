<?
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult["SHOW_HELP_SPOTLIGHT"] = false;
if (Loader::includeModule('bitrix24'))
{
	$licensePrefix = \CBitrix24::getLicensePrefix();
	if (in_array($licensePrefix, ["br", "fr", "en"]))
	{
		$arResult["SHOW_HELP_SPOTLIGHT"] = true;
	}
}