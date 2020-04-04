<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/.left.menu_ext.php");

$aMenuLinks = Array(
	Array(
		GetMessage("MENU_STRUCTURE"),
		"/company/vis_structure.php",
		Array("/company/structure.php"),
		Array("menu_item_id"=>"menu_structure"),
		""
	),
	Array(
		GetMessage("MENU_EMPLOYEE"),
		"/company/",
		Array(),
		Array("menu_item_id"=>"menu_employee"),
		""
	)
);

if (IsModuleInstalled("lists"))
{
	if (
		!IsModuleInstalled("bitrix24")
		|| CModule::IncludeModule("bitrix24") && \Bitrix\Bitrix24\Feature::isFeatureEnabled("lists_processes")
	)
	{
		$aMenuLinks[] = Array(
			GetMessage("MENU_LISTS"),
			"/company/lists/",
			Array(),
			Array("menu_item_id"=>"menu_lists"),
			""
		);
	}
}
?>