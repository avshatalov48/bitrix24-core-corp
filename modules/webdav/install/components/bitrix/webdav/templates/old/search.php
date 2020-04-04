<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent("bitrix:search.page", "tags", array(
	"RESTART" => "N",
	"CHECK_DATES" => "N",
	"USE_TITLE_RANK" => "N",
	
	"arrWHERE" => array("iblock_".$arParams["IBLOCK_TYPE"]), 
	"arrFILTER" => array("iblock_".$arParams["IBLOCK_TYPE"]), 
	"arrFILTER_iblock_".$arParams["IBLOCK_TYPE"] => array($arParams["IBLOCK_ID"]), 
	
	"TAGS_SORT" => "NAME",
	"TAGS_PERIOD" => "",
	"TAGS_URL_SEARCH" => CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["search"], array()),
	
	"PAGE_RESULT_COUNT" => $arParams["TAGS_PAGE_ELEMENTS"],
	"TAGS_PAGE_ELEMENTS" => $arParams["TAGS_PAGE_ELEMENTS"],
	"PERIOD_NEW_TAGS" => $arParams["TAGS_PERIOD"],
	"TAGS_INHERIT" => $arParams["TAGS_INHERIT"],
	
	"FONT_MAX" => $arParams["TAGS_FONT_MAX"],
	"FONT_MIN" => $arParams["TAGS_FONT_MIN"],
	"COLOR_NEW" => $arParams["TAGS_COLOR_NEW"],
	"COLOR_OLD" => $arParams["TAGS_COLOR_OLD"],
	"SHOW_CHAIN" => $arParams["TAGS_SHOW_CHAIN"],
	
	"CACHE_TIME" => $arParams["CACHE_TIME"],
	"CACHE_TYPE" => $arParams["CACHE_TYPE"],
	
	"WIDTH" => "100%",
	"bxpiwidth" => "620", 
	"SHOW_WHERE" => "N",
	"COLOR_TYPE" => "Y",
	"AJAX_MODE" => "N",
	"PAGER_TEMPLATE" => $arParams["PAGE_NAVIGATION_TEMPLATE"],
	), 
	$component,
	array("HIDE_ICONS" => "Y")
);
?><?
if ($arParams["SET_TITLE"] != "N")
	$APPLICATION->SetTitle(GetMessage("P_TITLE"));
?>