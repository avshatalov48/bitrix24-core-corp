<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$arResult["FEED_WHERE"] = array(
	"LAST" => array(),
	"CONTACTS" => array(),
	"COMPANIES" => array(),
	"LEADS" => array(),
	"DEALS" => array(),
);
if (!empty($arResult["FEED_DESTINATION"]))
{
	if (!empty($arResult["FEED_DESTINATION"]["CONTACTS"]))
	{
		$arResult["FEED_WHERE"]["CONTACTS"] = $arResult["FEED_DESTINATION"]["CONTACTS"];
		unset($arResult["FEED_DESTINATION"]["CONTACTS"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["COMPANIES"]))
	{
		$arResult["FEED_WHERE"]["COMPANIES"] = $arResult["FEED_DESTINATION"]["COMPANIES"];
		unset($arResult["FEED_DESTINATION"]["COMPANIES"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["LEADS"]))
	{
		$arResult["FEED_WHERE"]["LEADS"] = $arResult["FEED_DESTINATION"]["LEADS"];
		unset($arResult["FEED_DESTINATION"]["LEADS"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["DEALS"]))
	{
		$arResult["FEED_WHERE"]["DEALS"] = $arResult["FEED_DESTINATION"]["DEALS"];
		unset($arResult["FEED_DESTINATION"]["DEALS"]);
	}
}

if (!empty($arResult["FEED_DESTINATION"]["LAST"]))
{
	if (!empty($arResult["FEED_DESTINATION"]["LAST"]["CONTACTS"]))
	{
		$arResult["FEED_WHERE"]["LAST"]["CONTACTS"] = $arResult["FEED_DESTINATION"]["LAST"]["CONTACTS"];
		unset($arResult["FEED_DESTINATION"]["LAST"]["CONTACTS"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["LAST"]["COMPANIES"]))
	{
		$arResult["FEED_WHERE"]["LAST"]["COMPANIES"] = $arResult["FEED_DESTINATION"]["LAST"]["COMPANIES"];
		unset($arResult["FEED_DESTINATION"]["LAST"]["COMPANIES"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["LAST"]["LEADS"]))
	{
		$arResult["FEED_WHERE"]["LAST"]["LEADS"] = $arResult["FEED_DESTINATION"]["LAST"]["LEADS"];
		unset($arResult["FEED_DESTINATION"]["LAST"]["LEADS"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["LAST"]["DEALS"]))
	{
		$arResult["FEED_WHERE"]["LAST"]["DEALS"] = $arResult["FEED_DESTINATION"]["LAST"]["DEALS"];
		unset($arResult["FEED_DESTINATION"]["LAST"]["DEALS"]);
	}

	if (!empty($arResult["FEED_DESTINATION"]["LAST"]["CRM"]))
	{
		$arResult["FEED_WHERE"]["LAST"]["CRM"] = $arResult["FEED_DESTINATION"]["LAST"]["CRM"];
		unset($arResult["FEED_DESTINATION"]["LAST"]["CRM"]);
	}
}

if (!empty($arResult["FEED_DESTINATION"]["SELECTED"]))
{
	foreach ($arResult["FEED_DESTINATION"]["SELECTED"] as $key => $value)
	{
		if (in_array($value, array('contacts', 'companies', 'leads', 'deals')))
		{
			$arResult["FEED_WHERE"]["SELECTED"][$key] = $value;
			unset($arResult["FEED_DESTINATION"]["SELECTED"][$key]);
		}
	}
}

?>