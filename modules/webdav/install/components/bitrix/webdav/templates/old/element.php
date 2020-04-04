<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$arInfo = $APPLICATION->IncludeComponent("bitrix:webdav.element.view", "", Array(
	"IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
	"IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"NAME_FILE_PROPERTY"	=>	$arParams["NAME_FILE_PROPERTY"],
	"PERMISSION" => $arParams["PERMISSION"], 
	"CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
	
	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
	"ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
	"HELP_URL" => $arResult["URL_TEMPLATES"]["help"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	
	"COLUMNS"	=>	$arParams["COLUMNS"],
	
	"SET_TITLE"	=>	$arParams["SET_TITLE"],
	"STR_TITLE" => $arParams["STR_TITLE"], 
	"SHOW_WEBDAV" => $arParams["SHOW_WEBDAV"], 
	
	"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
	"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
	"DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>
<?
if(is_array($arInfo) && $arInfo["ELEMENT_ID"] && $arParams["USE_COMMENTS"]=="Y" && IsModuleInstalled("forum")):
$bShowHide = (intval($arInfo["ELEMENT"]["PROPERTIES"]["FORUM_TOPIC_ID"]["VALUE"]) <= 0 && 	
	($arParams['WORKFLOW'] == "bizproc" && $arInfo["ELEMENT"]["BP_PUBLISHED"] != "Y" || 
		$arParams['WORKFLOW'] == "workflow" && (!(intval($arInfo["ELEMENT"]["WF_STATUS_ID"]) == 1 && intval($arInfo["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0))));
//	ShowNote(GetMessage("WD_NOTE_EL"));
?>
<hr class="wd-hr" />
<?$APPLICATION->IncludeComponent(
	"bitrix:forum.topic.reviews",
	"",
	Array(
		"FORUM_ID" => $arParams["FORUM_ID"],
		"ELEMENT_ID" => $arInfo["ELEMENT_ID"],
		
		"URL_TEMPLATES_READ" => "",
		"URL_TEMPLATES_PROFILE_VIEW" => str_replace("#USER_ID#", "#UID#", $arResult["URL_TEMPLATES"]["user_view"]),
		"URL_TEMPLATES_DETAIL" => "",
		
		"POST_FIRST_MESSAGE" => "Y", 
		"POST_FIRST_MESSAGE_TEMPLATE" => GetMessage("WD_TEMPLATE_MESSAGE"), 
		"SUBSCRIBE_AUTHOR_ELEMENT" => "Y", 
		"IMAGE_SIZE" => false, 
		"MESSAGES_PER_PAGE" => $arParams["COMMENTS_COUNT"],
		"DATE_TIME_FORMAT" => false, 
		"USE_CAPTCHA" => $arParams["USE_CAPTCHA"],
		"PREORDER" => $arParams["PREORDER"],
		"PAGE_NAVIGATION_TEMPLATE" => false, 
		"DISPLAY_PANEL" => "N", 
		
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		
		
		"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
		"SHOW_LINK_TO_FORUM" => "N",
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?><?
endif;
?>
