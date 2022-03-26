<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/telephony/.left.menu_ext.php");

$licensePrefix = "";
if (CModule::IncludeModule("bitrix24"))
{
	$licensePrefix = CBitrix24::getLicensePrefix();
}
if (CModule::IncludeModule('voximplant') && \Bitrix\Voximplant\Security\Helper::isMainMenuEnabled())
{
	$aMenuLinks[] = Array(
		GetMessage("MENU_TELEPHONY_CONNECT"),
		"/telephony/index.php",
		Array(),
		Array("menu_item_id"=>"menu_telephony_start"),
		""
	);
	if(\Bitrix\Voximplant\Security\Helper::isBalanceMenuEnabled())
	{
		$aMenuLinks[] = [
			GetMessage("MENU_TELEPHONY_DETAIL"),
			"/telephony/detail.php",
			[],
			[
				"menu_item_id" => "menu_telephony_detail",
				"onclick" => "BX.SidePanel.Instance.open('/telephony/detail.php')",
			],
			""
		];
	}

	if (\Bitrix\Main\Loader::includeModule('report'))
	{
		\Bitrix\Main\UI\Extension::load('report.js.analytics');
		$aMenuLinks[] = Array(
			GetMessage("MENU_TELEPHONY_ANALYTICS"),
			"/report/telephony/?analyticBoardKey=telephony_calls_dynamics",
			Array(),
			Array("menu_item_id" => "menu_telephony_reports"),
			""
		);
	}
}
?>