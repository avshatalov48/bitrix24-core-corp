<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/contact_center/.left.menu_ext.php");

$aMenuLinks[] = array(
	GetMessage("MENU_CONTACT_CENTER"),
	"/contact_center/",
	array(),
	array("menu_item_id" => "menu_contact_center"),
	""
);

if (CModule::IncludeModule("imopenlines"))
{
	if (\Bitrix\ImOpenlines\Security\Helper::isStatisticsMenuEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_CONTACT_CENTER_IMOL_DETAILED_STATISTICS"),
			"/contact_center/openlines/statistics.php",
			array(),
			array("menu_item_id" => "menu_contact_center_detail_statistics"),
			""
		);
		$aMenuLinks[] = array(
			GetMessage("MENU_CONTACT_CENTER_IMOL_STATISTICS"),
			"/contact_center/openlines/",
			array(),
			array("menu_item_id" => "menu_contact_center_statistics"),
			""
		);
	}
}