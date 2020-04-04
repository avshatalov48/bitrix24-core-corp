<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;
CUtil::InitJSCore(array('window'));
CPageOption::SetOptionString("main", "nav_page_in_session", "N");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
// activation rating
CRatingsComponentsMain::GetShowRating($arParams);
$currentUserID = $USER->GetID();
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["RESOURCE_TYPE"] = "IBLOCK";
	if (!is_object($arParams["OBJECT"]))
	{
		$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
		$arParams["OBJECT"]->IsDir();
	}
	$ob = $arParams["OBJECT"];

	$arParams["IBLOCK_TYPE"] = $ob->IBLOCK_TYPE;
	$arParams["IBLOCK_ID"] = $ob->IBLOCK_ID;
	$arParams["ROOT_SECTION_ID"] = ($ob->arRootSection ? $ob->arRootSection["ID"] : false);
	$arParams["~SECTION_ID"] = $arParams["SECTION_ID"] = $ob->arParams["item_id"];
	$arParams["CHECK_CREATOR"] = ($arParams["OBJECT"]->check_creator ? "Y" : "N");
	$arParams["USE_COMMENTS"] = ($arParams["USE_COMMENTS"] == "Y" && IsModuleInstalled("forum") ? "Y" : "N");
	$arParams["NAME_FILE_PROPERTY"] = $ob->file_prop;
	$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["SORT_BY"] = (!empty($arParams["SORT_BY"]) ? $arParams["SORT_BY"] : "NAME");
	$arParams["SORT_ORD"] = ($arParams["SORT_ORD"] != "DESC" ? "ASC" : "DESC");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#",
		"section_edit" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#&ACTION=#ACTION#",

		"element" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#",
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_history" => "PAGE_NAME=element_history&ELEMENT_ID=#ELEMENT_ID#",
		"element_history_get" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#",
		"element_version" => "PAGE_NAME=element_version&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_versions" => "PAGE_NAME=element_version&ELEMENT_ID=#ELEMENT_ID#",
		"element_upload" => "PAGE_NAME=element_upload&SECTION_ID=#SECTION_ID#",

		"help" => "PAGE_NAME=help",
		"user_view" => "PAGE_NAME=user_view&USER_ID=#USER_ID#",

		"webdav_bizproc_view" => "PAGE_NAME=webdav_bizproc_view&ELEMENT_ID=#ELEMENT_ID#",
		"webdav_start_bizproc" => "PAGE_NAME=webdav_start_bizproc&ELEMENT_ID=#ELEMENT_ID#",
		"webdav_task_list" => "PAGE_NAME=webdav_task_list", 
		"webdav_task" => "PAGE_NAME=webdav_task&ID=#ID#");
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action", "edit_section"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
/***************** ADDITIONAL **************************************/
	$arParams["WORKFLOW"] = (!$ob->workflow ? "N" : $ob->workflow); 
	$arParams["DOCUMENT_ID"] = $arParams["DOCUMENT_TYPE"] = $arParams["OBJECT"]->wfParams["DOCUMENT_TYPE"];
	$arParams["COLUMNS"] = (is_array($arParams["COLUMNS"]) ? $arParams["COLUMNS"] : array("NAME", "TIMESTAMP_X", "USER_NAME", "FILE_SIZE"));
	$arParams["PAGE_ELEMENTS"] = intVal(intVal($arParams["PAGE_ELEMENTS"]) > 0 ? $arParams["PAGE_ELEMENTS"] : 50);
	$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
	$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
	$arParams["DEFAULT_EDIT"] = ($arParams["DEFAULT_EDIT"] == "N" ? "N" : "Y");
	$arParams["BASE_URL"] = $ob->base_url_full; 
	$arParams["NAME_TEMPLATE"] = (empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat() : trim($arParams['NAME_TEMPLATE']));
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["STR_TITLE"] = trim($arParams["STR_TITLE"]);
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/
	$bDialog = (isset($_REQUEST['dialog']) || isset($_REQUEST['dialog2']));

if ($arParams["PERMISSION"] < "R") 
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
elseif (isset($ob->meta_state) && ($arParams["PERMISSION"] < $ob->meta_names[$ob->meta_state]['rights']))
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}
if(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk') && !empty($arParams['OBJECT']))
{
	if(!empty($arParams['OBJECT']->arParams['dir_array']))
	{
		/** @var \Bitrix\Disk\File $diskFolder */
		$diskFolder = \Bitrix\Disk\Folder::load(array('XML_ID' => $arParams['OBJECT']->arParams['dir_array']['ID']), array('STORAGE'));
		if($diskFolder)
		{
			LocalRedirect(\Bitrix\Disk\Driver::getInstance()->getUrlManager()->getPathInListing($diskFolder) . '/' . $diskFolder->getName());
		}
	}
	elseif($arParams['OBJECT']->_path == '/')
	{
		if(!empty($arParams['OBJECT']->attributes['user_id']))
		{
			$diskStorage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($arParams['OBJECT']->attributes['user_id']);
		}
		elseif(!empty($arParams['OBJECT']->attributes['group_id']))
		{
			$diskStorage = \Bitrix\Disk\Driver::getInstance()->getStorageByGroupId($arParams['OBJECT']->attributes['group_id']);
		}
		if($diskStorage)
		{
			LocalRedirect(\Bitrix\Disk\Driver::getInstance()->getUrlManager()->getPathInListing($diskStorage->getRootObject()));
		}
	}
}

/********************************************************************
				Default params
********************************************************************/
	$arResult["DATA"] = array();
	$arResult["GRID_DATA"] = array(); 
	$arResult["GRID_DATA_COUNT"] = 0; 
	$arResult["SECTION"] = $ob->arParams["dir_array"];
	$arResult["STATUSES"] = array();
	$arResult["ERROR_MESSAGE"] = "";
	$arResult["NAV_CHAIN"] = $ob->GetNavChain();
	$arResult["NAV_CHAIN_UTF8"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), true);
	
	$arNavChain = $arResult["NAV_CHAIN"]; $sCurrentFolder = array_pop($arNavChain);
	$arResult["URL"] = array(
		"UP" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arNavChain))),
		"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))),
		"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
		"SECTIONS_DIALOG" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"]."?dialog=Y", array("PATH" => implode("/", $arResult["NAV_CHAIN"]))),
		"HELP" => CComponentEngine::MakePathFromTemplate($arParams["HELP_URL"], array()),
		"UPLOAD" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_UPLOAD_URL"], array("PATH" => $ob->_uencode($path, array("convert" => "full", "urlencode" => "N")), "SECTION_ID" => $arParams["SECTION_ID"])),
		"SECTION_EDIT" => CComponentEngine::MakePathFromTemplate($arParams["SECTION_EDIT_URL"], array("PATH" => $ob->_uencode($path, array("convert" => "full", "urlencode" => "N")), "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "ADD"))
	);
	$arUsersCache = array();
	if ($arParams["PERMISSION"] > "U")
		$arResult["SECTION_LIST"] = $ob->GetSectionsTree(array("path" => "/"));
	if (!empty($sCurrentFolder))
	{
		$arResult["GRID_DATA_COUNT"] = -1; 
		$arResult["GRID_DATA"][] = array(
			"id" => "", 
			"data" => array(), 
			"actions" => array(
			), 
			"columns" => array(
				"NAME" => '<div class="section-up"><div><a href="'.$arResult["URL"]["UP"].'"><span></span></a></div>'.
					'<a href="'.$arResult["URL"]["UP"].'">..</a></div>'), 
			"editable" => false);
	}
	
	$cache = new CPHPCache;
	$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName);
	
	$bShowSubscribe = false;
	$arParams["FORUM_CAN_VIEW"] = "N";
	$arResult["USER"] = array(
		"SHOW" => array(
			"SUBSCRIBE" => "N"), 
		"SUBSCRIBE" => array());
	$arResult["WF_STATUSES"] = array();
	$arResult["WF_STATUSES_PERMISSION"] = array();
	$arResult["ROOT_SECTION"] = $ob->arRootSection;
	$arParams["GRID_ID"] = "WebDAV".$arParams["IBLOCK_ID"]; 
	
	if (empty($aColumns))
		$aColumns = $arParams["COLUMNS"]; 
	if (!$bDialog)
	{
		/* Quote from main.interface.grid */
		$aOptions = CUserOptions::GetOption("main.interface.grid", $arParams["GRID_ID"], array());
		/* ... */
		if(!is_array($aOptions["views"]))
			$aOptions["views"] = array();
		if(!array_key_exists("default", $aOptions["views"]))
			$aOptions["views"]["default"] = array("columns"=>"");
		if($aOptions["current_view"] == '' || !array_key_exists($aOptions["current_view"], $aOptions["views"]))
			$aOptions["current_view"] = "default";
		/* ... */
		$aCurView = $aOptions["views"][$aOptions["current_view"]];
		$aColumns = explode(",", $aCurView["columns"]);
		global $by, $order;
		InitSorting();
		if (!$by)
		{
			$by = (!empty($aCurView["sort_by"]) ? $aCurView["sort_by"] : $arParams["SORT_BY"]);
			$order = (!empty($aCurView["sort_order"]) ? $aCurView["sort_order"] : $arParams["SORT_ORD"]);
		}

		/* /Quote from main.interface.grid */

		$arResult["FILTER"] = array();
		$arResult["FILTER"][] = array("id" => "content", "name" => GetMessage("WD_TITLE_CONTENT"), "default" => true, "type" => "search");
		$arResult["FILTER"][] = array("id" => "timestamp", "name" => GetMessage("WD_WHEN"), "default" => true, "type" => "date");

		$arFileTypes = @unserialize(COption::GetOptionString("webdav", "file_types"));
		$arFilterFileTypes = array("" => "");
		if ($arFileTypes !== false)
		{
			foreach ($arFileTypes as $arFileType)
			{
				$arFilterFileTypes[$arFileType["ID"]] = $arFileType["NAME"];
			}
			$arResult["FILTER"][] = array("id" => "doctype", "default" => true, "name" => GetMessage("WD_DOCTYPE"), "type" => "list", "items" => $arFilterFileTypes);
		}

		$arResult["FILTER"][] = array("id" => "?TAGS", "name" => GetMessage("WD_TITLE_TAGS"), "type" => "tags");
		$arResult["FILTER"][] = array("id" => "user", "enable_settings" => false,  "name" => GetMessage("WD_WHO"), "type" => "user");
		$arResult["FILTER"][] = array("id" => "FILE_SIZE", "name" => GetMessage("WD_TITLE_FILE_SIZE"), "type" => "number");
		$arResult["FILTER"][] = array("id" => "WF_LOCK_STATUS", "name" => GetMessage("WD_LOCK_STATUS"), "type" => "list", "items" => array(
			"" => "",
			"yellow" => GetMessage("WD_DOCSTATUS_YELLOW"),
			"red" => GetMessage("WD_DOCSTATUS_RED"), 
			"green" => GetMessage("WD_DOCSTATUS_GREEN")
		));
		if (isset($_REQUEST['?TAGS']))
		{
			$_REQUEST['?TAGS'] = str_replace("\\'", "'", htmlspecialcharsBack(urldecode($_REQUEST['?TAGS'])));
		}
		/************** Workflow *******************************************/
		if ($arParams["WORKFLOW"] == "workflow")
		{
			$db_res = CWorkflowStatus::GetDropDownList("Y",  "desc");
			if ($db_res && $res = $db_res->Fetch())
			{
				do 
			{
				$res["REFERENCE"] = preg_replace("/^(\[\d+\] )/", "", $res["REFERENCE"]);
				$arResult["WF_STATUSES"][$res["REFERENCE_ID"]] = htmlspecialcharsbx($res["REFERENCE"]);
				$arResult["WF_STATUSES_PERMISSION"][$res["REFERENCE_ID"]] = ($arParams["PERMISSION"] < "W" ? 
					CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]) : 2);
			}while ($res = $db_res->Fetch());
			}
			$arResult["STATUSES"] = $arResult["WF_STATUSES"];
		}
		elseif ($arParams["WORKFLOW"] == "bizproc")
		{
			$arParams["BIZPROC_START"] = false;
			$arTemplates = array();
			if ($arParams["PERMISSION"] >= "U")
			{
				$cache_id = '/bizproc/'.$arParams['IBLOCK_ID']."/bizproc_templates";
				if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path_main))
				{
					$arTemplates = $cache->GetVars();
				}
				else
				{
					$db_res = CBPWorkflowTemplateLoader::GetList(
						array(),
						array("DOCUMENT_TYPE" => $arParams["DOCUMENT_TYPE"]),
						false,
						false,
						array("ID", "AUTO_EXECUTE", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "PARAMETERS", "TEMPLATE")
					);
					while ($arWorkflowTemplate = $db_res->GetNext())
					{
						$arTemplates[$arWorkflowTemplate["ID"]] = $arWorkflowTemplate;
					}
					if ($arParams["CACHE_TIME"] > 0):
						$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path_main);
						$cache->EndDataCache($arTemplates);
					endif;
				}
			}
			$arParams["TEMPLATES"] = $arTemplates;
		}

		$grid_options = new CGridOptions($arParams["GRID_ID"]);
		$gridSort = $grid_options->GetSorting(array("sort"=>array("NAME" => "desc")));
		global $by, $order;
		if(is_array($gridSort["sort"]))
		{
			$by = key($gridSort["sort"]);
			$order = $gridSort["sort"][$by];
		}
		$arFilter = array();
		if (isset($_REQUEST["clear_filter"]) && $_REQUEST["clear_filter"] == "Y")
		{
			$urlParams = array();
			foreach($arResult["FILTER"] as $id => $arFilter)
			{
				if ($arFilter['id'] == 'FILE_SIZE')
				{
					$urlParams[] = $arFilter["id"]."_from";
					$urlParams[] = $arFilter["id"]."_to";
					$urlParams[] = $arFilter["id"]."_multiply";
				}
				elseif ($arFilter["type"] == "user")
				{
					$urlParams[] = $arFilter["id"];
					$urlParams[] = $arFilter["id"]."_name";
				}
				elseif ($arFilter["type"] == "number")
				{
					$urlParams[] = $arFilter["id"]."_from";
					$urlParams[] = $arFilter["id"]."_to";
				}
				elseif ($arFilter["type"] == "date")
				{
					$urlParams[] = $arFilter["id"]."_datesel";
					$urlParams[] = $arFilter["id"]."_days";
					$urlParams[] = $arFilter["id"]."_from";
					$urlParams[] = $arFilter["id"]."_to";
				}
				else
				{
					$urlParams[] = $arFilter["id"];
				}
			}
			$urlParams[] = "clear_filter";
			$grid_filter = $grid_options->GetFilter(array());

			if (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') === false)
				LocalRedirect($arResult['URL']['~THIS']);
			else
				LocalRedirect($ob->_uencode($arResult['URL']['~THIS'],array("utf8" => "Y", "convert" => "full")));
		}

		$grid_filter = $grid_options->GetFilter(array_merge($arResult["FILTER"], array(array('id'=>'FILE_SIZE_multiply'))));
		foreach ($grid_filter as $key => $value)
		{
			if(substr($key, -5) == "_from")
			{
				$new_key = substr($key, 0, -5). "_1";
			}
			elseif(substr($key, -3) == "_to")
			{
				$new_key = substr($key, 0, -3). "_2";
			}
			else
			{
				$new_key = $key;
			}

			$arFilter[$new_key] = $value;
		}
		$arResult["FILTER_VALUE"] = $arFilter;
		if ((sizeof($arFilter) > 0) && ($this->__parent))
		{
			$this->__parent->arResult["arButtons"] = (is_array($this->__parent->arResult["arButtons"]) ? 
				$this->__parent->arResult["arButtons"] : array()); 

			array_unshift($this->__parent->arResult["arButtons"], array(
				"TEXT" => GetMessage("WD_GO_BACK"),
				"PREORDER" => true,
				"TITLE" => GetMessage("WD_GO_BACK_ALT"),
				"LINK" => "javascript:bxGrid_".$arParams['GRID_ID'].".ClearFilter(document.forms['filter_".$arParams['GRID_ID']."']);",
				"ICON" => "btn-list go-back"));
		}

		/************** Columns ********************************************/
		if ($arParams["WORKFLOW"] == "bizproc_limited") 
			$arParams["COLUMNS"][] = "BP_PUBLISHED";
		elseif ($arParams["WORKFLOW"] == "bizproc")
			$arParams["COLUMNS"] = array_merge($arParams["COLUMNS"], array("BP_PUBLISHED",/*"BIZPROC",*//* "VERSIONS"*/));
		else
			$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("BP_PUBLISHED",/*"BIZPROC",*/ "VERSIONS")); 

		if ($arParams["SHOW_WORKFLOW"] != "Y" || $arParams["WORKFLOW"] != "workflow")
			$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("WF_STATUS_ID", "WF_NEW", "WF_COMMENTS"));  
		if ($arParams["PERMISSION"] < "U")
			$arParams["COLUMNS"] = array_diff($arParams["COLUMNS"], array("ACTIVE", "GLOBAL_ACTIVE", "SORT", "CODE", "EXTERNAL_ID", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO")); 
		$arParams["COLUMNS"] = array_unique($arParams["COLUMNS"]);
	}
/********************************************************************
				/Default params
********************************************************************/
if ($bDialog)
{
	$obDavEventHandler = CWebDavSocNetEvent::GetRuntime();
	if (isset($obDavEventHandler->arPath['PATH_TO_USER']) &&
		isset($obDavEventHandler->arPath['PATH_TO_GROUP']))
	{
		$userFileRoot = "files/lib/";
		$groupFileRoot = "files/";
		$fileListDefaultPath = "#path#";
		$fileElementDefaultPath = "element/view/#element_id#/";
		$fileEditDefaultPath = "element/edit/#element_id#/#action#/";
		$fileGetDefaultPath = "element/historyget/#element_id#/#element_name#";

		$userUrl = $obDavEventHandler->arPath['PATH_TO_USER'];
		$groupUrl = $obDavEventHandler->arPath['PATH_TO_GROUP'];

		$currentUserGroups = CIBlockWebdavSocnet::GetUserGroups($currentUserID);
		foreach($currentUserGroups as $groupID=>$arGroup)
		{
			$currentUserGroups[$groupID]["PATH_FILES"] = str_replace(array("#group_id#", "#path#"), array($groupID, ''), $groupUrl.$groupFileRoot.$fileListDefaultPath);
		}
		$arResult['USER_GROUPS'] =& $currentUserGroups;
	}

	$arJSParams = array();
	$arJSParams['entity_name'] = false;
	$arJSParams['entity_type'] = false;
	$arJSParams['entity_id'] = false;
	if (isset($ob->attributes['user_id']))
	{
		$arJSParams['entity_type'] = 'user';
		$arJSParams['entity_id'] = $ob->attributes['user_id'];
	}
	elseif (isset($ob->attributes['group_id']))
	{
		$arJSParams['entity_type'] = 'group';
		$arJSParams['entity_id'] = $ob->attributes['group_id'];
	}
	if (!!$arJSParams['entity_type'])
		$arJSParams['entity_name'] = $ob->arRootSection['NAME'];
	$requestUrl = WDAddPageParams($arResult['URL']['SECTIONS_DIALOG'], array("dialog2" => "Y", "ajax_call"=>"Y"), false);
	$arJSParams['requestRoot'] = $requestUrl;
	$arJSParams['iblockID'] = $ob->IBLOCK_ID;
	$arResult['JSPARAMS'] = $arJSParams;
}
/********************************************************************
				ACTIONS
********************************************************************/
$GLOBALS["APPLICATION"]->ResetException();
$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action.php");
$result = include($path);
if ($result !== true)
{
	$oError = $GLOBALS["APPLICATION"]->GetException();
	if ($oError)
		$arResult["ERROR_MESSAGE"] = $oError->GetString();
}
if ($bDialog)
{
	$arResult['JSPARAMS']['element_url'] = str_replace("#ACTION#", "VIEW", $arParams['ELEMENT_EDIT_URL']);
}
/********************************************************************
				/ACTIONS
********************************************************************/

/********************************************************************
				Data
********************************************************************/
/************** Forum subscribe ************************************/
if (!$bDialog && $arParams["USE_COMMENTS"] == "Y" && CModule::IncludeModule("forum"))
{
	$arParams["USE_COMMENTS"] = $arParams["FORUM_CAN_VIEW"] = (CForumNew::CanUserViewForum($arParams["FORUM_ID"], $GLOBALS['USER']->GetUserGroupArray()) ? "Y" : "N");
	if ($arParams["FORUM_CAN_VIEW"] == "Y" && $GLOBALS['USER']->IsAuthorized())
	{
		$bShowSubscribe = true;
		$arUserSubscribe = array();
		$cache_id = "/" . $arParams['IBLOCK_ID'] . "/forum_user_subscribe_".intVal($currentUserID)."_".$arParams["FORUM_ID"];

		if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path_main))
		{
			$res = $cache->GetVars();
			$arUserSubscribe = $res["arUserSubscribe"];
		}
		else
		{
			$db_res = CForumSubscribe::GetList(array(), array("USER_ID" => $currentUserID, "FORUM_ID" => $arParams["FORUM_ID"]));
			$arUserSubscribe = array();
			if ($db_res && $res = $db_res->Fetch())
			{
				do
				{
					$arUserSubscribe[] = $res;
				} while ($res = $db_res->Fetch());
			}

			$arUserSubscribe = array(
				"USER_ID" => intVal($currentUserID),
				"DATA" => $arUserSubscribe);

			if ($arParams["CACHE_TIME"] > 0):
				$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path_main);
				$cache->EndDataCache(array("arUserSubscribe" => $arUserSubscribe));
			endif;
		}
		$arResult["USER"]["SUBSCRIBE"] = $arUserSubscribe["DATA"];
		if (is_array($arResult["USER"]["SUBSCRIBE"]))
		{
			$arTmp = array("FORUM" => "N", "TOPIC" => "N", "TOPICS" => array());
			foreach ($arResult["USER"]["SUBSCRIBE"] as $res)
			{
				if (intVal($res["FORUM_ID"]) > 0 && intVal($res["TOPIC_ID"]) <= 0)
				{
					$arTmp["FORUM"] = "Y";
					$bShowSubscribe = false;
				}
				else
				{
					$arTmp["TOPIC"] = "Y";
					$arTmp["TOPICS"][$res["TOPIC_ID"]] = $res;
				}
			}
			$arResult["USER"]["SUBSCRIBE"] += $arTmp;
		}
		$arResult["USER"]["SHOW"]["SUBSCRIBE"] = ($bShowSubscribe ? "Y" : "N");
	}
}

$arResult['USER_FIELDS'] = $ob->GetUfFields();
/************** Data ***********************************************/
//if (empty($arResult["DATA"]))
{
	$arResult['BP_PARAM_REQUIRED'] = ($ob->BPParameterRequired() ? 'Y' : 'N');
	if (preg_match("/[#=](doc|sec)(\d+)/", $_SERVER['REQUEST_URI'], $matches))
	{
		if ($matches[1] == "doc") 
			$hilightID = "E";
		else
			$hilightID = "S";
		$hilightID .= $matches[2];
	}

	$arSelectedFields = array_merge($arParams["COLUMNS"], $aColumns);

	if ($arParams["USE_COMMENTS"] == "Y")
	{
		$arSelectedFields[] = "PROPERTY_FORUM_MESSAGE_CNT";
		$arSelectedFields[] = "PROPERTY_FORUM_TOPIC_ID";
	}
	
	$options = array("path" => $ob->_path, "depth" => 1);

	$res = $ob->PROPFIND($options, $files, array("FILTER" => $arFilter, "COLUMNS" => $arSelectedFields, "return" => "nav_result", "get_clones" => "Y")); 
	if (is_string($res)) // some error
	{
		$oError = $GLOBALS["APPLICATION"]->GetException();
		if ($oError)
			$arResult["ERROR_MESSAGE"] = $oError->GetString();
		if (!empty($arResult["ERROR_MESSAGE"]))
			ShowError($arResult["ERROR_MESSAGE"]);
		return;
	}

	$arResult["SECTION"] = $res["SECTION"]; 
	$arResult["NAV_RESULT"] = $res["NAV_RESULT"];

	$arGridOptions = CUserOptions::GetOption("main.interface.grid", $arParams['GRID_ID'], array());
	if (isset($arGridOptions['views']['default']['page_size']) && intval($arGridOptions['views']['default']['page_size'])>0)
	{
		$arParams["PAGE_ELEMENTS"] = intval($arGridOptions['views']['default']['page_size']);
	}

	if ($arResult["NAV_RESULT"])
	{
		if (!$bDialog && $arParams["PAGE_ELEMENTS"] > 0)
		{
			$pageID = false;
			if (isset($hilightID))
			{
				$hilightPos = array_search($hilightID, array_keys($arResult["NAV_RESULT"]->arResult));
				$pageID = intval($hilightPos / $arParams["PAGE_ELEMENTS"])+1;
			}
			$arResult["NAV_RESULT"]->NavStart($arParams["PAGE_ELEMENTS"], false, $pageID);
			$arResult["NAV_STRING"] = $arResult["NAV_RESULT"]->GetPageNavStringEx($navComponentObject, GetMessage("WD_DOCUMENTS"), $arParams["PAGE_NAVIGATION_TEMPLATE"], true);
		}
		$sTaskUrl = "";
		$arIconHash = array();

		$allowableIblockForSymlink = false;
		$possibleIblockCode = array('user_files', 'group_files', 'shared_files');
		$currentIblockCode = '';
		foreach($possibleIblockCode as $type)
		{
			$wdIblockOptions = \CWebDavIblock::libOptions($type, false, SITE_ID);
			if (is_set($wdIblockOptions, 'id') && (intval($wdIblockOptions['id']) > 0))
			{
				if($ob->IBLOCK_ID == $wdIblockOptions['id'])
				{
					CWebDavIblock::$possibleUseSymlinkByInternalSections = $allowableIblockForSymlink = $type != 'group_files';
					$currentIblockCode = $type;
				}
			}
		}

		$selfSharedSections = $sectionsIds = $dataNavResults = array();
		while ($res = $arResult["NAV_RESULT"]->Fetch())
		{
			$dataNavResults[] = $res;
			if ($res["TYPE"] == "S")
			{
				$sectionsIds[] = $res['ID'];
			}
		}
		if($sectionsIds)
		{

			//todo optimize!
			$filter = array(
				'SECTION_ID' => $sectionsIds,
			);
			if($currentIblockCode && $currentIblockCode == 'shared_files')
			{
				$filter['USER_ID'] = $USER->getId();
			}
			$querySelfSharedSections = \Bitrix\Webdav\FolderInviteTable::getList(array(
				'filter' => $filter,
			    'select' => array('ID', 'SECTION_ID', 'IBLOCK_ID'),
			));
			while($folderInvite = $querySelfSharedSections->fetch())
			{
				$selfSharedSections[$folderInvite['SECTION_ID']] = $folderInvite;
			}
			unset($folderInvite);
		}

		foreach($dataNavResults as $res)
		{
			if (
				isset($res["~NAME"])
				&& $res["~NAME"] === $ob->meta_names['TRASH']['name']
			)
				continue;

			if ($res["TYPE"] == "S")
			{
				if(!empty($res[CWebDavIblock::UF_LINK_SECTION_ID]))
				{
					$res['LINK'] = array(
						'SECTION_ID' => $res[CWebDavIblock::UF_LINK_SECTION_ID],
						'IBLOCK_ID' => $res[CWebDavIblock::UF_LINK_IBLOCK_ID],
						'CAN_FORWARD' => $res[CWebDavIblock::UF_LINK_CAN_FORWARD],
					);
				}
				if($res["TYPE"] == "S" && isset($selfSharedSections[$res['ID']]))
				{
					$res['SHARED_SECTION'] = array(
						'SECTION_ID' => $selfSharedSections['SECTION_ID'],
						'IBLOCK_ID' => $selfSharedSections['IBLOCK_ID'],
						'USER_ID' => $selfSharedSections['USER_ID'],
					);
				}
				if (in_array("SECTION_CNT", $aColumns) || in_array("SECTIONS_CNT", $aColumns))
					$res["SECTION_CNT"] = $res["SECTIONS_CNT"] = intVal(
						CIBlockSection::GetCount(array(
							"IBLOCK_ID"=>$arParams["IBLOCK_ID"],
							"SECTION_ID"=>$res["ID"])));
				if (in_array("ELEMENT_CNT", $aColumns) || in_array("ELEMENTS_CNT", $aColumns))
					$res["ELEMENT_CNT"] = $res["ELEMENTS_CNT"] = intVal(
						CIBlockSection::GetSectionElementsCount(
							$res["ID"], Array("CNT_ALL"=>"Y")));
			}
			
			$res["~PATH"] = $res["PATH"]; 
			$res["PATH"] = $ob->_uencode($res["~PATH"], array("utf8" => "Y", "convert" => $arParams["CONVERT"])); 

			if ($arParams['USE_COMMENTS'] === 'Y' && CModule::IncludeModule('forum'))
			{
				$res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"] = CForumTopic::GetMessageCount($arParams["FORUM_ID"], $res["PROPERTY_FORUM_TOPIC_ID_VALUE"], true);

				if ($res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"] !== false)
					$res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]--;

			} else {
				$res["PROPERTY_FORUM_MESSAGE_CNT_VALUE"] = false;
			}

/*********************** Name **************************************/
			//$res["NAME"] = WrapLongWords($res["NAME"]);
/*********************** Path **************************************/
			__prepare_item_info($res, $arParams); 
/*********************** Actions ***********************************/
			// Subscribe
			if ($res["TYPE"] != "S" && $res["SHOW"]["SUBSCRIBE"] == "Y")
				$res["SUBSCRIBE"] = (!empty($arResult["USER"]["SUBSCRIBE"]["TOPICS"][$res["PROPERTY_FORUM_TOPIC_ID_VALUE"]]) ? "N" : "Y");
/*********************** Custom properties *************************/
			if ($res["TYPE"] == "E" && !$bDialog)
			{
				foreach ($arSelectedFields as $propSelectName)
				{
					if (
						( substr($propSelectName, 0, 9) == "PROPERTY_" ) &&
						($propSelectName !== 'PROPERTY_FORUM_MESSAGE_CNT') &&
						($propSelectName !== 'PROPERTY_FORUM_TOPIC_ID')
					)
					{
						$propName = substr($propSelectName, 9);
						$prop = null;
						$dbProps = CIBlockElement::GetProperty($arParams["IBLOCK_ID"], $res['ID'], array("sort" => "asc"), array('code' => $propName));
						if ($dbProps)
						{
							while($arProps = $dbProps->Fetch())
							{
								if (!$prop)
								{
									$prop = $arProps;
								}
								else
								{
									if (!is_array($prop))
										$prop = array($prop);
									$prop[] = $arProps;
								}
							}
						}

						if((is_array($prop["VALUE"]) && count($prop["VALUE"])>0) ||
							(!is_array($prop["VALUE"]) && strlen($prop["VALUE"])>0))
						{
							$prop = CIBlockFormatProperties::GetDisplayValue($res, $prop, 'webdav_link');
							if (is_array($prop['DISPLAY_VALUE']))
							{
								$res[$propSelectName] = array_pop($prop['DISPLAY_VALUE']);
							} else {
								$res[$propSelectName] = $prop['DISPLAY_VALUE'];
							}
						}
					}
				}
			}
			$arResult["DATA"][$res["ID"]] = $res;
/************** Grid Data ******************************************/
			$arParams["RATING_TAG"] = 'N';
			if ($arParams["SHOW_RATING"] == 'Y')
				$arParams["RATING_TAG"] = 'Y';

			$rs = __build_item_info($res, $arParams, true);
			$arActions = $rs["actions"];
			$arResult['preview'][] = $arActions['preview_launch'];
			unset($arActions['preview_launch']);
			$aCols = $rs["columns"]; 
			$aCols["USER_NAME"] = $aCols["MODIFIED_BY"]; 
			$aCols["CREATED_USER_NAME"] = $aCols["CREATED_BY"]; 
			
			if ($res["TYPE"] == "E")
			{
				foreach ($res as $key => $val)
				{
					if (substr($key, 0, 9) == "PROPERTY_" && substr($key, -6, 6) == "_VALUE")
					{
						$tmp = substr($key, 0, strlen($key) - 6); 
						$res[$tmp] = $val; 
					}
				}

				if (
					isset($res['USER_FIELDS'])
					&& is_array($res['USER_FIELDS'])
				)
				{
					foreach($res['USER_FIELDS'] as $fieldCode => $arUserField)
					{
						//$arUserField["EDIT_FORM_LABEL"] = StrLen($arUserField["EDIT_FORM_LABEL"]) > 0 ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];
						//$arUserField["~EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"];
						//$arUserField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arUserField["EDIT_FORM_LABEL"]);

						$tmpVal = "";

						if (
							(
								( is_array($arUserField["VALUE"])
									&& count($arUserField["VALUE"]) == 0
								)
								|| ( !is_array($arUserField["VALUE"])
									&& !$arUserField["VALUE"]
								)
							)
							&& $arUserField["USER_TYPE_ID"] != "boolean"
						)
							continue;

						ob_start();
						$APPLICATION->IncludeComponent(
							"bitrix:system.field.view", 
							$arUserField["USER_TYPE_ID"], 
							array("arUserField" => $arUserField),
							null,
							array("HIDE_ICONS"=>"Y")
						);
						$tmpVal = ob_get_clean();

						$aCols[$fieldCode] = $tmpVal;
					}
				}

				$aCols["LOCKED_USER_NAME"] = $aCols["WF_LOCKED_BY"];
				if ($res["SHOW"]["SUBSCRIBE"] == "Y")
				{
					if ($res["SUBSCRIBE"] == "Y")
						$arActions["element_subscribe"] = array(
							"ICONCLASS" => "element_subscribe",
							"TITLE" => GetMessage("WD_SUBSCRIBE_ELEMENT"),
							"TEXT" => GetMessage("WD_SUBSCRIBE"),
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~SUBSCRIBE"])."');"); 
					else
						$arActions["element_subscribe"] = array(
							"ICONCLASS" => "element_unsubscribe",
							"TITLE" => GetMessage("WD_UNSUBSCRIBE_ELEMENT"),
							"TEXT" => GetMessage("WD_UNSUBSCRIBE"),
							"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~UNSUBSCRIBE"])."');"); 
				}

				$aCols['PREVIEW_TEXT'] = TruncateText(HTMLToTxt($res['PREVIEW_TEXT']), 255);
				if ($arParams["WORKFLOW"] == 'workflow')
				{
					$aCols["WF_STATUS_ID"] = $arResult["STATUSES"][$res['WF_STATUS_ID']]; 
				}
				elseif ($arParams["WORKFLOW"] == 'bizproc')
				{
					$aCols["WF_STATUS_ID"] = ($res["WF_STATUS_ID"] == 2) ? GetMessage("WD_STATUS_NOT_PUBLISHED") : GetMessage("WD_STATUS_PUBLISHED"); 
				}
				else
				{
					$aCols["WF_STATUS_ID"] = GetMessage("WD_STATUS_PUBLISHED");
				}
				$aCols["LOCK_STATUS"] = '<div class="element-lamp-'.$res["LOCK_STATUS"].'" title="'.(
					$res["LOCK_STATUS"] == "green" ? GetMessage("IBLOCK_GREEN_ALT") : 
						($res["LOCK_STATUS"] == "yellow" ? GetMessage("IBLOCK_YELLOW_ALT") : GetMessage("IBLOCK_RED_ALT"))).'"></div>'. 
						(($res['LOCK_STATUS']=='red' && $res['LOCKED_USER_NAME']!='') ? $aCols['LOCKED_USER_NAME'] : ''); 

				$arChildren = array(); 
				if (!$bDialog && ($arParams["WORKFLOW"] == "bizproc") && !empty($res["CHILDREN"]))
				{
					foreach ($res["CHILDREN"] as $k => $rs) 
					{
						$arBProcesses = $arFlags = array(); 
						if (is_array($rs["arDocumentStates"]) && !empty($rs["arDocumentStates"]))
						{
							foreach ($rs["arDocumentStates"] as $key => $arDocumentState)
							{
								if (!(strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0))
									continue; 
								$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($currentUserID, $arDocumentState["ID"]);
								$bTasks = !empty($arTasksWorkflow); 
								$arFlags["tasks"] = ($arFlags["tasks"] == true ? true : $bTasks);
								$arFlags["inprogress"]++; 
								$arBProcesses[] = 
									'<div class="bizproc-item-title">'. 
										'<div class="bizproc-statuses bizproc-status-'.($bTasks ? "attention" : "inprogress").'"></div>'. 
										(!empty($arDocumentState["TEMPLATE_NAME"]) ? $arDocumentState["TEMPLATE_NAME"] : GetMessage("IBLIST_BP")).': '.
										'<span class="bizproc-item-title bizproc-state-title" style="margin-left:1em;">'. 
											'<a href="'.WDAddPageParams(CComponentEngine::MakePathFromTemplate( $arParams["~ELEMENT_URL"], 
											array("ELEMENT_ID" => $rs["ID"], "ACTION" => "EDIT")), 
											array("webdavForm".$arParams["IBLOCK_ID"]."_active_tab"=>"tab_bizproc_view"))
											.'">'.
												($arDocumentState["STATE_TITLE"] ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]).
											'</a>'. 
										'</span>'.
									'</div>';
							}
						}
						foreach (array("MODIFIED_BY"/*, "CREATED_BY", "WF_LOCKED_BY"*/) as $user_key)
						{
							$rs[$user_key] = (is_array($rs[$user_key]) ? $rs[$user_key] : __parse_user($rs[$user_key], $arParams["USER_VIEW_URL"]));
							$rs[$user_key] = $rs[$user_key]["LINK"];
						}
						$tmp =	'<div class="bizproc-item-title">'.
									'<div class="bizproc-statuses"></div>'.
									'<span class="bizproc-item-title bizproc-state-title">'.
										'<a href="'.CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"],
											array("ELEMENT_ID" => $rs["ID"], "ACTION" => "EDIT")).'">'.
												str_replace("'", "&#39;", htmlspecialcharsbx($rs["NAME"])).'</a> ('.$rs["MODIFIED_BY"].')'.
									'</span>'.
								'</div>';
						if (!empty($arBProcesses))
						{
							$tmp =
								'<div class="bizproc-item-title">'.
									'<div class="bizproc-statuses bizproc-status-'.($arFlags["tasks"] ? "attention" : "inprogress").'"></div>'.
									'<span class="bizproc-item-title bizproc-state-title">'.
										'<a href="'.CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"],
											array("ELEMENT_ID" => $rs["ID"], "ACTION" => "EDIT")).'">'.str_replace("'", "&#39;", htmlspecialcharsbx($rs["NAME"])).'</a> ('.$rs["MODIFIED_BY"].')'.
									'</span>'.
									'<img src="/bitrix/images/1.gif" onload="WDTooltip'.$rs['ID'].'(this)" />'.
								'</div>';
							echo
									'<script>
									function WDTooltip'.$rs['ID'].'(elm) {
										BX.hint_replace(elm, "'.CUtil::JSEscape('<ol class="bizproc-items"><li>'.implode("</li><li>", $arBProcesses).'</li></ol>').'");
									}
									</script>
									';
						}
						$arChildren[$k] = $tmp;
					}
				}
				if (!empty($arChildren))
				{
					$versions = array(
						"0" => "WD_VERSIONS_0",
						"1" => "WD_VERSIONS_1",
						"10_20" => "WD_VERSIONS_10_20",
						"MOD_1" => "WD_VERSIONS_MOD_1",
						"MOD_2_4" => "WD_VERSIONS_MOD_2_4",
						"MOD_OTHER" => "WD_VERSIONS_MOD_OTHER"
					);

					$arVerLines = array();
					foreach ($arChildren as $k => $tmp) {
						$arVerLines[] = '{text :  \''.$tmp.'\', title : \'\', className : "wd-version-popup", href : "" }';
					}

					$aCols["NAME"] .= '
						<script type="text/javascript">
							if(!window.versionPopup){var versionPopup = {};}
							versionPopup["'.$res["ID"].'"] = [ '.implode(",\n", $arVerLines).' ];
						</script>
					';
					$aCols["NAME"] .= "<div class=\"wd-bp-versions\" id=\"wd_versions_".$res["ID"]."\">(<span onclick='return WDShowVersionsPopup(".$res["ID"].", this);'>".GetMessage("WD_VERSIONS_COUNT", array('#NUM#' => count($arChildren), '#VERSIONS#' => _FormatDateMessage(count($arChildren), $versions)))."</span>)</div>";
				}
				else 
				{
					unset($arActions["element_versions"]); 
				}

			}
			$editable = ($res["TYPE"] == "S" && (sizeof($arActions) > 1)) || ($res["TYPE"] == "E" && ($res["SHOW"]["EDIT"] === "Y" || $res["SHOW"]["DELETE"] === "Y")); 

			$res["BASE_URL_FOR_EXT_LINK"] = $arParams["OBJECT"]->base_url;
			$res["URL_FOR_EXT_LINK"] = $res["PATH"];
			
			$arIconHash[] = md5($res["BASE_URL_FOR_EXT_LINK"] . $res["URL_FOR_EXT_LINK"]);

			$arResult["GRID_DATA"][] = array(
				"id" => $res["TYPE"].$res["ID"], 
				"data" => $res, 
				"actions" => array_values($arActions), 
				"columns" => $aCols, 
				"editable" => $editable);
/************** Grid Data/******************************************/
		}
		unset($dataNavResults, $selfSharedSections, $res);
		if(!empty($arResult['preview']))
		{
			CJSCore::Init(array('viewer'));
		}
		
		$arResult["EXT_LINKS_HASH_ARRAY"] = array();
		$resExtLinks = CWebDavExtLinks::GetList(array("ONLY_CURRENT_USER" => true,"URL_HASH" => $arIconHash), array("URL_HASH"), array("COUNT" => true));
		while($arResExtLinks = $resExtLinks->Fetch())
		{
			$arResult["EXT_LINKS_HASH_ARRAY"][$arResExtLinks["URL_HASH"]] = $arResExtLinks["CT"];
		}
		
	}

	$arResult["GRID_DATA_COUNT"] += count($arResult["GRID_DATA"]); 
}

/*************** Users *********************************************/
$arResult["USERS"] = $arUsersCache;
/*************** For custom Templates ******************************/

/********************************************************************
				/Data
********************************************************************/

if (isset($_REQUEST['dialog2']) && ($_REQUEST['dialog2'] == 'Y'))
	$this->__templateName = 'dialog2';
$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$title = (empty($arParams["STR_TITLE"]) ? GetMessage("WD_TITLE") : $arParams["STR_TITLE"]);
	$GLOBALS["APPLICATION"]->SetTitle(empty($sCurrentFolder) ? htmlspecialcharsBack($title) : $sCurrentFolder);
}

if ($arParams["SET_NAV_CHAIN"] == "Y" && !empty($sCurrentFolder))
{
	$res = array(); 
	foreach ($arNavChain as $name)
	{
		$res[] = $name; 
		$GLOBALS["APPLICATION"]->AddChainItem($name, 
			CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $res))));
	}
	$GLOBALS["APPLICATION"]->AddChainItem($sCurrentFolder, $arResult["URL"]["THIS"]);
}

if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized())
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/
?>
