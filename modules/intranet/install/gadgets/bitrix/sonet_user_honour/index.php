<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork") && !CModule::IncludeModule("intranet"))
	return false;

?><?$APPLICATION->IncludeComponent(
		"bitrix:intranet.structure.honour.user",
		"gadget",
		array(
			"ID" => $arGadgetParams["USER_ID"],
			"NUM_ENTRIES" => $arGadgetParams["NUM_ENTRIES"],
		),
		false,
		Array("HIDE_ICONS"=>"Y")
);
?>