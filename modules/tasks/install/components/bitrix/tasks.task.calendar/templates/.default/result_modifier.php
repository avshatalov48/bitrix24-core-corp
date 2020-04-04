<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Tasks\Util;

//region TITLE
if($arParams['GROUP_ID'] > 0)
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

if (is_array($arResult["LIST"]))
{
	foreach($arResult["LIST"] as $k=>$arTaskItem)
	{
		$arResult["LIST"][$k]['TITLE'] = htmlspecialcharsbx($arTaskItem['TITLE']);
		$arResult["LIST"][$k]['GROUP_NAME'] = htmlspecialcharsbx($arTaskItem['GROUP_NAME']);

		$arResult["LIST"][$k]['RESPONSIBLE_NAME'] = htmlspecialcharsbx($arTaskItem['RESPONSIBLE_NAME']);
		$arResult["LIST"][$k]['RESPONSIBLE_LAST_NAME'] = htmlspecialcharsbx($arTaskItem['RESPONSIBLE_LAST_NAME']);
		$arResult["LIST"][$k]['RESPONSIBLE_SECOND_NAME'] = htmlspecialcharsbx($arTaskItem['RESPONSIBLE_SECOND_NAME']);
		$arResult["LIST"][$k]['RESPONSIBLE_LOGIN'] = htmlspecialcharsbx($arTaskItem['RESPONSIBLE_LOGIN']);

		$arResult["LIST"][$k]['CREATED_BY_NAME'] = htmlspecialcharsbx($arTaskItem['CREATED_BY_NAME']);
		$arResult["LIST"][$k]['CREATED_BY_LAST_NAME'] = htmlspecialcharsbx($arTaskItem['CREATED_BY_LAST_NAME']);
		$arResult["LIST"][$k]['CREATED_BY_SECOND_NAME'] = htmlspecialcharsbx($arTaskItem['CREATED_BY_SECOND_NAME']);
		$arResult["LIST"][$k]['CREATED_BY_LOGIN'] = htmlspecialcharsbx($arTaskItem['CREATED_BY_LOGIN']);
	}
}


if (isset($arParams[ "SET_NAVCHAIN" ]) && $arParams[ "SET_NAVCHAIN" ] != "N")
{
	$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE"));
}
/** END:TITLE */
