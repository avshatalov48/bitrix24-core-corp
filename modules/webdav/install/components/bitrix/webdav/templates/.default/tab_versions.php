<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

if ($arParams['OBJECT']->CheckRight($arResult["VARIABLES"]["PERMISSION"], "element_edit") < "U")
	return false;

$arCurFileInfo = pathinfo(__FILE__);
$langfile = trim(preg_replace("'[\\\\/]+'", "/", ($arCurFileInfo['dirname']."/lang/".LANGUAGE_ID."/".$arCurFileInfo['basename'])));
__IncludeLang($langfile);

    if (!isset($arInfo["ELEMENT"]["ORIGINAL"]) || empty($arInfo["ELEMENT"]["ORIGINAL"]))
    {

        $sCurrentTab = (isset($_GET[$arParams["FORM_ID"].'_active_tab']) ? $_GET[$arParams["FORM_ID"].'_active_tab']: '');
        $_GET[$arParams["FORM_ID"].'_active_tab'] = 'tab_version';

        ob_start();
        $APPLICATION->IncludeComponent("bitrix:webdav.element.version", ".default", Array(
            "IBLOCK_TYPE"	=>	$arParams["IBLOCK_TYPE"],
            "IBLOCK_ID"	=>	$arParams["IBLOCK_ID"],
            "ELEMENT_ID"	=>	$arResult["VARIABLES"]["ELEMENT_ID"],
            "NAME_FILE_PROPERTY"	=>	$arParams["NAME_FILE_PROPERTY"],
            "PERMISSION" => $arParams["PERMISSION"], 
            "CHECK_CREATOR" => $arParams["CHECK_CREATOR"],
            
            "SECTIONS_URL" => $arResult["URL_TEMPLATES"]["sections_short"],
            "SECTION_EDIT_URL" => $arResult["URL_TEMPLATES"]["section_edit"],
            "ELEMENT_URL" => $arResult["URL_TEMPLATES"]["element"],
            "ELEMENT_EDIT_URL" => $arResult["URL_TEMPLATES"]["element_edit"],
            //"ELEMENT_FILE_URL" => $arResult["URL_TEMPLATES"]["element_file"],
            "ELEMENT_HISTORY_URL" => $arResult["URL_TEMPLATES"]["element_history"],
            "ELEMENT_HISTORY_GET_URL" => $arResult["URL_TEMPLATES"]["element_history_get"],
            "HELP_URL" => $arResult["URL_TEMPLATES"]["help"],
            "USER_VIEW_URL" => $arResult["URL_TEMPLATES"]["user_view"],
            "WEBDAV_BIZPROC_VIEW_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_view"], 
            "WEBDAV_BIZPROC_VERSIONS_URL" => $arResult["URL_TEMPLATES"]["webdav_bizproc_versions"], 
            "WEBDAV_START_BIZPROC_URL" => $arResult["URL_TEMPLATES"]["webdav_start_bizproc"], 
            "WEBDAV_TASK_URL" => $arResult["URL_TEMPLATES"]["webdav_task"], 
            "WEBDAV_TASK_LIST_URL" => $arResult["URL_TEMPLATES"]["webdav_task_list"], 
            "FORM_ID" => $arParams["FORM_ID"],
            "TAB_ID" => 'tab_version',

            "SET_NAV_CHAIN"	=>	"N",
            "SET_TITLE"	=>	"N",
            "STR_TITLE" => "N", 
            "SHOW_WEBDAV" => $arParams["SHOW_WEBDAV"], 
            
            "CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
            "CACHE_TIME"	=>	$arParams["CACHE_TIME"],
            "DISPLAY_PANEL"	=>	$arParams["DISPLAY_PANEL"]),
            $component,
            array("HIDE_ICONS" => "Y")
        );

        $this->__component->arResult['TABS'][] = 
            array( "id" => "tab_version", 
                   "name" => GetMessage("WD_VERSIONS"), 
                   "title" => GetMessage("WD_EV_TITLE"), 
                   "fields" => array(
                       array(  "id" => "WD_VERSIONS", 
                                "name" => GetMessage("WD_VERSIONS"), 
                                "colspan" => true,
                                "type" => "custom", 
                                "value" => ob_get_clean()
                            )
                    ) 
            );

        unset($_GET[$arParams["FORM_ID"].'_active_tab']);
        if ($sCurrentTab !== ''):
            $_GET[$arParams["FORM_ID"].'_active_tab'] = $sCurrentTab;
        endif;
    }
?>
