<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/bizproc/.left.menu_ext.php");

$aMenuLinks = Array(
	Array(
		GetMessage("MENU_BIZPROC_TASKS"),
		"/company/personal/bizproc/",
		Array(),
		Array("counter_id" => "bp_tasks", "menu_item_id" => "menu_bizproc"),
		""
	)
);
if (CModule::IncludeModule("lists") && CLists::isFeatureEnabled())
{
	$aMenuLinks[] = Array(
		GetMessage("MENU_MY_PROCESS"),
		"/company/personal/processes/",
		Array(),
		Array("menu_item_id" => "menu_my_processes"),
		""
	);
}
if (CModule::IncludeModule("lists") && CLists::isFeatureEnabled())
{
	$aMenuLinks[] = Array(
		GetMessage("MENU_PROCESS_STREAM"),
		"/bizproc/processes/",
		Array(),
		Array("menu_item_id" => "menu_processes"),
		""
	);
}
$aMenuLinks[] = Array(
	GetMessage("MENU_BIZPROC_ACTIVE"),
	"/bizproc/bizproc/",
	Array(),
	Array("menu_item_id" => "menu_bizproc_active"),
	""
);
?>