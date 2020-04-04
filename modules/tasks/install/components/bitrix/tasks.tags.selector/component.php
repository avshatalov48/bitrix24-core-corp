<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult["USER_TAGS"] = array();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

$dbRes = CTaskTags::getTagsNamesByUserId($USER->getId());
$arResult["~USER_TAGS"] = $arResult["USER_TAGS"] = array();
while($tag = $dbRes->GetNext())
{
	$arResult["USER_TAGS"][] = $tag["NAME"];
	$arResult["~USER_TAGS"][] = $tag["~NAME"];
}

if (isset($arParams["VALUE"]) && $arParams["VALUE"])
{
	if (!is_array($arParams["VALUE"]))
	{
		$arResult["VALUE"] = explode(",", $arParams["VALUE"]);
		$arResult["~VALUE"] = explode(",", $arParams["~VALUE"]);
	}
	else
	{
		$arResult["VALUE"] = $arParams["VALUE"];
		$arResult["~VALUE"] = $arParams["~VALUE"];
	}
}
else
{
	$arResult["VALUE"] = $arResult["~VALUE"] = array();
}

if (sizeof($arResult["VALUE"]) > 0)
{
	$arResult["VALUE"] = array_map("trim", $arResult["VALUE"]);
	$arResult["~VALUE"] = array_map("trim", $arResult["~VALUE"]);
}

$arResult["NAME"] = htmlspecialcharsbx($arParams["NAME"]);
$arResult["~NAME"] = $arParams["NAME"];

$this->IncludeComponentTemplate();
