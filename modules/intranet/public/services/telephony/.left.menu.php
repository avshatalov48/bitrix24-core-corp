<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/telephony/.left.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_BALANCE"),
		"/services/telephony/index.php",
		Array("/services/telephony/detail.php"),
		Array("menu_item_id"=>"menu_telephony_balance"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY"),
		"/services/telephony/settings.php",
		Array("/services/telephony/edit.php"),
		Array("menu_item_id"=>"menu_telephony_settings"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_NUMBER"),
		"/services/telephony/numbers.php",
		Array(),
		Array("menu_item_id"=>"menu_telephony_numbers"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_BLACKLIST"),
		"/services/telephony/blacklist.php",
		Array(),
		Array("menu_item_id"=>"menu_telephony_blacklist"),
		""
	),
);
?>