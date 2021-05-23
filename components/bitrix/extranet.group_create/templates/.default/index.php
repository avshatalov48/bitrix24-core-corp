<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group_create", 
	"", 
	Array(
		"PATH_TO_USER" 			=> $arParams["PATH_TO_USER"],
		"PATH_TO_GROUP" 		=> $arResult["PATH_TO_GROUP"],
		"PATH_TO_GROUP_EDIT" 	=> $arResult["PATH_TO_GROUP_EDIT"],
		"PATH_TO_GROUP_CREATE" 	=> $arResult["PATH_TO_GROUP_CREATE"],
		"PAGE_VAR" 				=> $arResult["ALIASES"]["page"],
		"USER_VAR" 				=> $arResult["ALIASES"]["user_id"],
		"GROUP_VAR" 			=> $arResult["ALIASES"]["group_id"],
		"SET_TITLE" 			=> "Y", 
		"USER_ID"				=> $arResult["VARIABLES"]["user_id"],
		"GROUP_ID" 				=> $arResult["VARIABLES"]["group_id"],
		"REDIRECT_CONFIRM" 		=> $arResult["PATH_TO_INVITE"],
		"NAME_TEMPLATE" 		=> $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" 			=> $arParams["SHOW_LOGIN"],
		"USE_AUTOSUBSCRIBE" 	=> "N",
	),
	$component 
);
?>
