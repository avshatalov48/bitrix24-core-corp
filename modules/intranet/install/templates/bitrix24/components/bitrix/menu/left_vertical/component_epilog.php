<?

use Bitrix\Main\UI\Extension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var @global CMain $APPLICATION */
global $APPLICATION;
$APPLICATION->AddHeadScript("/bitrix/js/main/dd.js");

Extension::load(["ajax", "fx"]);