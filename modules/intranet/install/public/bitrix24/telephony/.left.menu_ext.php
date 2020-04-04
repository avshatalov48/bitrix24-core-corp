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
		$aMenuLinks[] = Array(
			GetMessage("MENU_TELEPHONY_DETAIL"),
			"/telephony/detail.php",
			Array(),
			Array("menu_item_id"=>"menu_telephony_detail"),
			""
		);
	}

}
?>