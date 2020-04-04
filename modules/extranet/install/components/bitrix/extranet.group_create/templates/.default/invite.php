<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group_request_search", 
	"", 
	Array(
		"PATH_TO_USER" => $arParams["PATH_TO_USER"],
		"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
		"USER_VAR" => $arResult["ALIASES"]["user_id"],
		"PAGE_VAR" => $arResult["ALIASES"]["page"],
		"GROUP_VAR" => $arResult["ALIASES"]["group_id"],
		"PATH_TO_SEARCH" => $arResult["PATH_TO_SEARCH"],
		"SET_TITLE" => "Y",
		"SET_NAVCHAIN" => "Y",
		"GROUP_ID" => $arResult["VARIABLES"]["group_id"],
		"ALLOW_SKIP" => "Y",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_SHOW" => $arParams["SHOW_LOGIN"],
	),
	$component 
);
?>
