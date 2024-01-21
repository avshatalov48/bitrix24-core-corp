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

	if (Bitrix\Main\Config\Option::get("sale", "~IS_SALE_CRM_SITE_MASTER_FINISH", "N") === 'Y')
	{
		$aMenuLinks[] = Array(
			GetMessage("MENU_ADMIN_PANEL"),
			"/bitrix/admin/",
			Array(),
			Array("menu_item_id" => "menu_admin_panel"),
			""
		);
	}
	if (
		(bool)(\Bitrix\Main\Loader::includeModule("bitrix24") &&
		\CBitrix24::IsPortalAdmin(\Bitrix\Main\Engine\CurrentUser::get()->getId()))
	)
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_MAIL_BLACKLIST"),
			"/settings/configs/mail_blacklist.php",
			Array(),
			Array("menu_item_id"=>"menu_mail_blacklist"),
			""
		);
	}
}
