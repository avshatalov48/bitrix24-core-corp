<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/updates/.left.menu_ext.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

$counterNum = "";
if ($arUpdateList = CUpdateClient::GetUpdatesList($errorMessage, LANGUAGE_ID))
{
	if (
		isset($arUpdateList["MODULES"])
		&& is_array($arUpdateList["MODULES"])
		&& isset($arUpdateList["MODULES"][0]["#"]["MODULE"])
		&& is_array($arUpdateList["MODULES"][0]["#"]["MODULE"])
	)
	{
		$counterNum = count($arUpdateList["MODULES"][0]["#"]["MODULE"]);
	}
}

$aMenuLinks = Array(
	Array(
		GetMessage("MENU_LICENSE"),
		"/updates/",
		Array("/updates/index.php"),
		Array("menu_item_id"=>"menu_updates_license"),
		""
	),
	Array(
		GetMessage("MENU_UPDATES"),
		"/updates/updates.php",
		Array(),
		Array("menu_item_id"=>"menu_updates_updates", "counter_num"=>$counterNum),
		""
	),
	Array(
		GetMessage("MENU_BACKUP"),
		"/updates/backup.php",
		Array(),
		Array("menu_item_id"=>"menu_updates_backup"),
		""
	)
);
?>