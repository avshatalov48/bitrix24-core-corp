<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arResult["AUTH_OTP_HELP_LINK"] = $APPLICATION->GetCurPageParam("help=Y");
$arResult["AUTH_OTP_LINK"] = $APPLICATION->GetCurPageParam("", array("help"));
?>