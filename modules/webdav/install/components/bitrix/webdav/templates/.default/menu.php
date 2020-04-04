<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//if ($arParams["SHOW_WEBDAV"] == "Y")
//{
	//$url_help = $arResult["URL_TEMPLATES"]["help"];
	//$url_base = $arParams["BASE_URL"];
	//$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/informer.php")));
	//include_once($file);
//}
if($this->__page == "section" && $arParams["SHOW_TAGS"] == "Y" && (intVal($arResult["VARIABLES"]["SECTION_ID"]) <= 0) && IsModuleInstalled("search")):
?><?$APPLICATION->IncludeComponent("bitrix:search.tags.cloud", ".default", Array(
		"SEARCH" => $arResult["REQUEST"]["~QUERY"],
		"TAGS" => $arResult["REQUEST"]["~TAGS"],

		"PAGE_ELEMENTS" => $arParams["TAGS_PAGE_ELEMENTS"],
		"PERIOD" => $arParams["TAGS_PERIOD"],
		"TAGS_INHERIT" => $arParams["TAGS_INHERIT"],

		"URL_SEARCH" =>  CComponentEngine::MakePathFromTemplate($arResult["URL_TEMPLATES"]["search"], array()),

		"FONT_MAX" => $arParams["TAGS_FONT_MAX"],
		"FONT_MIN" => $arParams["TAGS_FONT_MIN"],
		"COLOR_NEW" => $arParams["TAGS_COLOR_NEW"],
		"COLOR_OLD" => $arParams["TAGS_COLOR_OLD"],
		"SHOW_CHAIN" => $arParams["TAGS_SHOW_CHAIN"],

		"WIDTH" => "100%",
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"arrFILTER" => array("iblock_".$arParams["IBLOCK_TYPE"]),
		"arrFILTER_iblock_".$arParams["IBLOCK_TYPE"] => array($arParams["IBLOCK_ID"])
		),
		$component,
		array("HIDE_ICONS" => "Y"));
	?><div class="empty-clear"></div><?
endif;
$page_name = str_replace("DISK_", "", strtoupper($this->__component->__page_webdav_template));
/*if ($page_name == "WEBDAV_TASK"):
	$GLOBALS["APPLICATION"]->AddChainItem(GetMessage("WD_TASK"),
		CComponentEngine::MakePathFromTemplate($arResult['URL_TEMPLATES']['webdav_task_list'], array()));
elseif ($page_name == "WEBDAV_BIZPROC_WORKFLOW_EDIT"):
	$GLOBALS["APPLICATION"]->AddChainItem(GetMessage("WD_BP"),
		CComponentEngine::MakePathFromTemplate($arResult['URL_TEMPLATES']['webdav_bizproc_workflow_admin'], array()));
endif;*/
if ($page_name == "SECTIONS")
{
	$component->arResult["arButtons"] = (is_array($component->arResult["arButtons"]) ? $component->arResult["arButtons"] : array());

	if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
	{
		if ($arParams['OBJECT']->CheckRight($arResult["VARIABLES"]["PERMISSION"], "iblock_rights_edit") >= "X")
		{
			$component->arResult["arButtons"][] = array(
				"TEXT" => GetMessage("WD_BUTTON_1"),
				"TITLE" => GetMessage("WD_BUTTON_1_ALT"),
				"LINK" => "javascript:".$APPLICATION->GetPopupLink(Array(
					"URL"=> "/bitrix/components/bitrix/webdav/templates/.default/iblock_settings.php?IBLOCK_ID=".$arParams["IBLOCK_ID"]."&back_url=".urlencode($APPLICATION->GetCurPage()),
					"PARAMS" => Array("min_width" => 450, "min_height" => 250)
				)),
				"ICON" => "btn-list settings");
		}
	}
	else
	{
		if ($arResult["VARIABLES"]["PERMISSION"] >= "X")
		{
			$component->arResult["arButtons"][] = array(
				"TEXT" => GetMessage("WD_BUTTON_2"),
				"TITLE" => GetMessage("WD_BUTTON_2_ALT"),
				"LINK" => "javascript:".$APPLICATION->GetPopupLink(Array(
					"URL"=>"/bitrix/admin/public_access_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&path=".urlencode($arParams["FOLDER"])."&back_url=".urlencode($APPLICATION->GetCurPage()),
					"PARAMS" => Array("min_width" => 450, "min_height" => 250)
				)),
				"ICON" => "btn-list rights");
		}
	}
}

?><?$result = $APPLICATION->IncludeComponent("bitrix:webdav.menu", "", Array(
	"OBJECT" => $arParams["OBJECT"],
	"SECTION_ID"	=>	$arResult["VARIABLES"]["SECTION_ID"],
	"ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
	"PAGE_NAME" => $page_name,
	"ACTION"	=>	$arResult["VARIABLES"]["ACTION"],
	"CONVERT"	=>	$arParams["CONVERT"],

	"SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections"],
	"SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"].
		(strpos($arResult["URL_TEMPLATES"]["section_edit"], "?") === false ? "?" : "&").
		"use_light_view=Y",
	"ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
	"ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
	"ELEMENT_UPLOAD_URL" => $arResult["URL_TEMPLATES"]["element_upload"],
	"HELP_URL" => $arResult["URL_TEMPLATES"]["help"],
	"CONNECTOR_URL" => $arResult["URL_TEMPLATES"]["connector"],
	"USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
	"WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"],
	"WEBDAV_BIZPROC_WORKFLOW_ADMIN_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_workflow_admin"],
	"WEBDAV_BIZPROC_WORKFLOW_EDIT_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_workflow_edit"],

	"USE_COMMENTS" => $arParams["USE_COMMENTS"],
	"FORUM_ID" => $arParams["FORUM_ID"],

	"SHOW_WEBDAV" => $arParams["SHOW_WEBDAV"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
$this->__component->__webdav_values = $result;

if ($arParams["SHOW_NAVIGATION"] != "N")
{
// text from main
	CMain::InitPathVars($site, $path);
	$DOC_ROOT = CSite::GetSiteDocRoot($site);

	$path = $GLOBALS["APPLICATION"]->GetCurDir();
	$arChain = Array();

	while(true)
	{
		$path = rtrim($path, "/");

		$chain_file_name = $DOC_ROOT.$path."/.section.php";
		if(file_exists($chain_file_name))
		{
			$sSectionName = "";
			include($chain_file_name);
			if(strlen($sSectionName)>0)
				$arChain[] = Array("TITLE"=>$sSectionName, "LINK"=>$path."/");
		}

		if($path.'/' == SITE_DIR)
			break;

		if(strlen($path)<=0)
			break;
		$pos = bxstrrpos($path, "/");
		if($pos===false)
			break;
		$path = substr($path, 0, $pos+1);
	}

	$GLOBALS["APPLICATION"]->IncludeComponent(
		"bitrix:breadcrumb",
		"webdav",
		Array(
			"START_FROM" => (count($arChain) + $this->__component->__page_webdav_chain_items - 1),
			"PATH" => "",
			"SITE_ID" => ""
		), $component,
		array("HIDE_ICONS" => "Y")
	);
}
?>
