<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Tasks\Util;

//region TITLE
if ($arParams['PROJECT_VIEW'] === 'Y')
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_PROJECT");
}
elseif($arParams['GROUP_ID'] > 0)
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_GROUP_TASKS");
}
else
{
	if ($arParams[ "USER_ID" ] == Util\User::getId())
	{
		$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_MY");
	}
	else
	{
		$sTitle = CUser::FormatName($arParams[ "NAME_TEMPLATE" ], $arResult[ "USER" ], true, false) . ": " . GetMessage("TASKS_TITLE");
		$sTitleShort = GetMessage("TASKS_TITLE");
	}
}
$APPLICATION->SetPageProperty("title", $sTitle);
$APPLICATION->SetTitle($sTitleShort);
//endregion TITLE

$APPLICATION->SetPageProperty("title", $sTitle);
$APPLICATION->SetTitle($sTitleShort);
\Bitrix\Main\UI\Extension::load("ui.notification");

if (isset($arParams[ "SET_NAVCHAIN" ]) && $arParams[ "SET_NAVCHAIN" ] != "N")
{
	$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE"));
}
/** END:TITLE */