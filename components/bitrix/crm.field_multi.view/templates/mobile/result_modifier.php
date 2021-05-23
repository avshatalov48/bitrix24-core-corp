<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (CModule::IncludeModule("crm"))
{
	$arResult["SHORT_NAMES"] = CCrmFieldMulti::GetEntityTypeList($arResult['TYPE_ID'], false);
}