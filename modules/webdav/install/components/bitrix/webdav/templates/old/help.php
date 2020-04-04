<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent("bitrix:webdav.help", "", Array(
	"BASE_URL"	=>	$arParams["BASE_URL"],
	"SET_TITLE"	=> $arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>