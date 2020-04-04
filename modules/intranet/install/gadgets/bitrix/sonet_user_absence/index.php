<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork") && !CModule::IncludeModule("intranet"))
	return false;

?><?$APPLICATION->IncludeComponent(
		"bitrix:intranet.absence.user",
		"gadget",
		array(
			"ID" => $arGadgetParams["USER_ID"],
			"CALENDAR_IBLOCK_ID" => $arGadgetParams["IBLOCK_ID"],
		),
		false,
		Array("HIDE_ICONS"=>"Y")
	);
?>