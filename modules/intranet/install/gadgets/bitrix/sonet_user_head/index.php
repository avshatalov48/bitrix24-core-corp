<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("socialnetwork") && !CModule::IncludeModule("intranet"))
	return false;

if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = false;
				
?><?$APPLICATION->IncludeComponent(
		"bitrix:intranet.structure.head.user",
		"gadget",
		array(
			"ID" => $arGadgetParams["USER_ID"],
			"DETAIL_URL" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
		),
		false,
		Array("HIDE_ICONS"=>"Y")
	);
?>