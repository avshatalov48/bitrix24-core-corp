<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/settings/configs/.left.menu_ext.php");

if ($GLOBALS['USER']->CanDoOperation('bitrix24_config'))
{
	$aMenuLinks[] = Array(
		GetMessage("MENU_CONFIGS"),
		"/settings/configs/",
		Array(),
		Array("menu_item_id"=>"menu_configs"),
		""
	);

	if (
		!IsModuleInstalled("bitrix24")
		&& \Bitrix\Main\Loader::includeModule("scale")
		&& \Bitrix\Scale\Helper::isScaleCanBeUsed()
		&& $_SERVER['BITRIX_ENV_TYPE'] == "crm"
	)
	{
		$aMenuLinks[] = Array(
			GetMessage("MENU_VM"),
			"/settings/configs/vm.php",
			Array(),
			Array("menu_item_id"=>"menu_configs_vm"),
			""
		);
	}

	if (
		\Bitrix\Main\Loader::includeModule("bitrix24")
		&& (
			\CBitrix24::IsLicensePaid()
			|| \CBitrix24::IsNfrLicense()
			|| \CBitrix24::IsDemoLicense()
		)
		|| !IsModuleInstalled("bitrix24")
	)
	{
		$aMenuLinks[] = Array(
			GetMessage("MENU_EVENT_LOG"),
			"/settings/configs/event_log.php",
			Array(),
			Array("menu_item_id"=>"menu_event_log"),
			""
		);
	}
}
?>