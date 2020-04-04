<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;
/** CAllMain $APPLICATION */
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["RESOURCE_TYPE"] = ($arParams["RESOURCE_TYPE"] == "FOLDER" ? "FOLDER" : "IBLOCK");
	$arParams["PAGE_NAME"] = strToUpper(trim($arParams["PAGE_NAME"]));	
	
	if (!is_object($arParams["OBJECT"]))
	{

		if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
			$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
		else 
			$arParams["OBJECT"] = new CWebDavFile($arParams, $arParams['BASE_URL']);
		if ($arParams["PAGE_NAME"] == "SECTIONS")
			$arParams["OBJECT"]->IsDir(); 
	}
	$ob = $arParams["OBJECT"]; 
	$arParams["ENTITY"] = null;
	if ($ob->Type == "iblock")
	{
		$arParams["RESOURCE_TYPE"] = "IBLOCK"; 
		$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
		$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
		$arParams["ROOT_SECTION_ID"] = intVal($arParams["ROOT_SECTION_ID"]);
		$arParams["SECTION_ID"] = intVal(!empty($arParams["SECTION_ID"]) ? $arParams["SECTION_ID"] : $_REQUEST["SECTION_ID"]);
		$arParams["ELEMENT_ID"] = intVal(!empty($arParams["ELEMENT_ID"]) ? $arParams["ELEMENT_ID"] : $_REQUEST["ELEMENT_ID"]);
		$arParams['ENTITY_TYPE'] = (($arParams['SECTION_ID'] > 0 || $arParams['SECTION_ID'] === 0) ? 'SECTION' : 'ELEMENT');
		$arParams["CHECK_CREATOR"] = ($arParams["OBJECT"]->check_creator ? "Y" : "N");
		$arParams["USE_COMMENTS"] = ($arParams["USE_COMMENTS"] == "Y" && IsModuleInstalled("forum") ? "Y" : "N");
		$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);
		if ($ob->e_rights)
		{
			if ($arParams['ENTITY_TYPE'] == 'SECTION')
			{
				if ($arParams['SECTION_ID'] > 0)
				{
					$arParams['PERMISSION'] = $ob->GetPermission($arParams['ENTITY_TYPE'], $arParams['SECTION_ID']);
				}
				else
				{
					$arParams['PERMISSION'] = $ob->GetPermission('IBLOCK', $ob->IBLOCK_ID);
				}
			}
			else
			{
				$arParams['PERMISSION'] = $ob->GetPermission($arParams['ENTITY_TYPE'], $arParams['ELEMENT_ID']);
			}
		}
		else
			$arParams["PERMISSION"] = $ob->permission;
	}
	else
	{
		$arParams["RESOURCE_TYPE"] = "FOLDER"; 
		$arParams["FOLDER"] = $ob->real_path; 
		$arParams["FOLDER_PATH"] = $ob->real_path_full; 
		$arParams["USE_COMMENTS"] = "N"; 
		$arParams["PERMISSION"] = $ob->permission;
	}
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#",
		"section_edit" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#&ACTION=#ACTION#",
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_upload" => "PAGE_NAME=element_upload&SECTION_ID=#SECTION_ID#", 
		"help" => "PAGE_NAME=help",
		"connector" => "PAGE_NAME=connector",
		"webdav_bizproc_view" => "PAGE_NAME=webdav_bizproc_view&ELEMENT_ID=#ELEMENT_ID#", 
		"webdav_bizproc_workflow_admin" => "PAGE_NAME=webdav_bizproc_workflow_admin", 
		"webdav_bizproc_workflow_edit" => "PAGE_NAME=webdav_bizproc_workflow_admin&ID=#ID#", 
		"webdav_task_list" => "PAGE_NAME=webdav_task_list");
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $APPLICATION->GetCurPage()."?".$URL_VALUE;
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") === false ? true : false);
	if (!$arParams["CONVERT"])
		$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#")); 
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["STR_TITLE"] = trim($arParams["STR_TITLE"]);
	$arParams["BASE_URL"] = trim($ob->base_url_full);
/********************************************************************
				/Input params
********************************************************************/

if ($ob->Errors)
{
	$e = new CAdminException($ob->Error);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	$err = $GLOBALS["APPLICATION"]->GetException(); 
	if ($err):
		ShowError($err->GetString());
	endif;
	return false; 
}

/********************************************************************
				Default params
********************************************************************/
	$arResult["URL"] = array(
		"SECTION" => array(),
		"ELEMENT" => array(), 
		"WEBDAV_BIZPROC_WORKFLOW_ADMIN" => CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_BIZPROC_WORKFLOW_ADMIN_URL"], array()), 
		"WEBDAV_BIZPROC_WORKFLOW_EDIT" => CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_BIZPROC_WORKFLOW_EDIT_URL"], array("ID" => 0)),
	);
	$arResult["ELEMENT"] = array();
	$bShowSubscribe = false;
	$arParams["FORUM_CAN_VIEW"] = "N";
	$arResult["USER"] = array(
		"SHOW" => array(), 
		"SUBSCRIBE" => array());
	$cache = new CPHPCache;
	$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName);
	$arParams["SHOW_WEBDAV"] = ($arParams["SHOW_WEBDAV"] == "N" ? "N" : "Y");
	$arResult["NAV_CHAIN"] = $ob->GetNavChain(array()); 
	$arResult["NAV_CHAIN_UTF8"] = $ob->GetNavChain(array(), true); 
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				Data
********************************************************************/
/*************** Show webdav interface *****************************/

	$arParams["SHOW_WEBDAV"] = (
		(
			($ob->CheckRight($arParams["PERMISSION"], "element_edit") >= "U")
		/*	&& (
				($ob->Type != 'iblock')
				|| (! $ob->IsBpParamRequired())
			)  */
		)
		? 'Y'
		: 'N'
	);

/*************** Forum subscribe to document ***********************/

if ($arParams["USE_COMMENTS"] == "Y" && $GLOBALS['USER']->IsAuthorized())
{
	CModule::IncludeModule("forum");
	$cache_id = "/".$arParams["IBLOCK_ID"]."/forum_user_subscribe_".intVal($GLOBALS["USER"]->GetID())."_".$arParams["FORUM_ID"];
	
	$arParams["FORUM_CAN_VIEW"] = (CForumNew::CanUserViewForum($arParams["FORUM_ID"], $GLOBALS['USER']->GetUserGroupArray()) ? "Y" : "N");
	
	if ($arParams["FORUM_CAN_VIEW"] == "Y" && $GLOBALS['USER']->IsAuthorized())
	{
		if ((!empty($_REQUEST["SUBSCRIBE_FORUM"]) || !empty($_REQUEST["subscribe_forum"])) && check_bitrix_sessid())
		{
			if ($_REQUEST["SUBSCRIBE_FORUM"] == "Y" || $_REQUEST["subscribe_forum"] == "Y")
			{
				ForumSubscribeNewMessagesEx($arParams["FORUM_ID"], 0, "N", $strErrorMessage = "", $strOKMessage = "");
			}
			elseif ($_REQUEST["SUBSCRIBE_FORUM"] == "N" || $_REQUEST["subscribe_forum"] == "N")
			{
				$arFilter = array(
					"USER_ID" => $GLOBALS["USER"]->GetId(), 
					"FORUM_ID" => $arParams["FORUM_ID"]);
				$db_res = CForumSubscribe::GetList(array(), $arFilter);
				if ($db_res && $res = $db_res->Fetch())
				{
					do 
					{
						if (CForumSubscribe::CanUserDeleteSubscribe($res["ID"], $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID()))
						{
							CForumSubscribe::Delete($res["ID"]);
						}
					}while ($res = $db_res->Fetch());
				}
			}
			BXClearCache(true, $cache_path_main);
			
			$arNavChain = ($arParams["CONVERT"] ? $arResult["NAV_CHAIN_UTF8"] : $arResult["NAV_CHAIN"]); 
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
				array("PATH" => implode("/", $arNavChain), "SECTION_ID" => $arParams["SECTION_ID"]));
		}
		
		$bShowSubscribe = true; $arUserSubscribe = array();
		
		if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path_main))
		{
			$res = $cache->GetVars();
			$arUserSubscribe = $res["arUserSubscribe"];
		}
		else
		{
			$arFields = array(
				"USER_ID" => $GLOBALS["USER"]->GetID(),
				"FORUM_ID" => $arParams["FORUM_ID"]);
			$db_res = CForumSubscribe::GetList(array(), $arFields);
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do
				{
					$arUserSubscribe[] = $res;
				} while ($res = $db_res->Fetch());
			}
			$arUserSubscribe = array(
				"USER_ID" => intVal($GLOBALS["USER"]->GetID()), 
				"DATA" => $arUserSubscribe);
			
			if ($arParams["CACHE_TIME"] > 0)
			{
				$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path_main);
				$cache->EndDataCache(array("arUserSubscribe"=>$arUserSubscribe));
			}
		}
		$arResult["USER"]["SUBSCRIBE"] = $arUserSubscribe["DATA"];
		if (is_array($arResult["USER"]["SUBSCRIBE"]))
		{
			foreach ($arResult["USER"]["SUBSCRIBE"] as $res)
			{
				if (intVal($res["FORUM_ID"]) > 0 && intVal($res["TOPIC_ID"]) <= 0)
				{
					$arResult["USER"]["SUBSCRIBE"]["FORUM"] = "Y";
					break;
				}
			}
		}
	}
}
$arResult["USER"]["SHOW"]["SUBSCRIBE"] = ($bShowSubscribe ? "Y" : "N");
/*************** Paths *********************************************/
$arNavChain = $arResult["NAV_CHAIN"];
$sCurrentFolder = array_pop($arNavChain); 
$arResult["URL"]["SECTION"]["UP"] = CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
	array("PATH" => implode("/", $arNavChain)));
$path = implode("/", $arResult["NAV_CHAIN"]); 
$arResult["URL"]["SECTION"]["ADD"] = CComponentEngine::MakePathFromTemplate($arParams["SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "ADD"));
$arResult["URL"]["SECTION"]["~ADD"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "ADD"));
$arResult["URL"]["SECTION"]["~POPUP_ADD"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
	array("PATH" => $ob->_uencode($path, array("convert" => "full", "urlencode" => "N")), "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "ADD"));
$arResult["URL"]["SECTION"]["EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "EDIT"));
$arResult["URL"]["SECTION"]["~EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "EDIT"));
$arResult["URL"]["SECTION"]["DROP"] = CComponentEngine::MakePathFromTemplate($arParams["SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "DROP"));
$arResult["URL"]["SECTION"]["~DROP"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "DROP"));
$arResult["URL"]["SECTION"]["EMPTY_TRASH"] = CComponentEngine::MakePathFromTemplate($arParams["SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $ob->GetMetaID('TRASH'), "ACTION" => "DROP"));
$arResult["URL"]["SECTION"]["~EMPTY_TRASH"] = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
	array("PATH" => $path, "SECTION_ID" => $ob->GetMetaID('TRASH'), "ACTION" => "DROP"));

$arResult["URL"]["ELEMENT"]["EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
	array("PATH" => $path, "ELEMENT_ID" => $arParams["ELEMENT_ID"], "ACTION" => "EDIT"));
$arResult["URL"]["ELEMENT"]["DELETE"] = CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], 
	array("PATH" => $path, "ELEMENT_ID" => $arParams["ELEMENT_ID"], "ACTION" => "DELETE"));
$arResult["URL"]["ELEMENT"]["DELETE"] = WDAddPageParams($arResult["URL"]["ELEMENT"]["DELETE"], 
	array("edit" => "Y", "sessid" => bitrix_sessid()));
$arResult["URL"]["ELEMENT"]["UPLOAD"] = CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_UPLOAD_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"]));
$arResult["URL"]["ELEMENT"]["ADD"] = CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"]));
$arResult["URL"]["ELEMENT"]["BP_VIEW"] = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_BIZPROC_VIEW_URL"], 
	array("ELEMENT_ID" => $arParams["ELEMENT_ID"]));
$arResult["URL"]["HELP"] = CComponentEngine::MakePathFromTemplate($arParams["HELP_URL"], array());
$arResult["URL"]["CONNECTOR"] = CComponentEngine::MakePathFromTemplate($arParams["CONNECTOR_URL"], array());
$arResult["URL"]["SUBSCRIBE"] = CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
	array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"]));
$arResult["URL"]["UNSUBSCRIBE"] = WDAddPageParams($arResult["URL"]["SUBSCRIBE"], array("subscribe_forum" => "N", "sessid" => bitrix_sessid()));
$arResult["URL"]["SUBSCRIBE"] = WDAddPageParams($arResult["URL"]["SUBSCRIBE"], array("subscribe_forum" => "Y", "sessid" => bitrix_sessid()));
/*************** For Custom components *****************************/
$arParams["USE_BIZPROC"] = ($ob->workflow == "bizproc" ? "Y" : "N"); 
$arParams["USE_WORKFLOW"] = ($ob->workflow == "workflow" ? "Y" : "N"); 
$arResult["URL"]["CHAIN"] = array(
	array(
		"URL" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => "", "SECTION_ID" => 0)),
		"TITLE" => (empty($arParams["STR_TITLE"]) ? GetMessage("WD_TITLE") : $arParams["STR_TITLE"])));

$arResult['BP_PARAM_REQUIRED'] = 'N';
if ($ob->Type == 'iblock')
	$arResult['BP_PARAM_REQUIRED'] = ($ob->BPParameterRequired() ? 'Y' : 'N');

/********************************************************************
				/Data
********************************************************************/

$arResult['GROUP_DISK'] = array();
if($ob->attributes['group_id'] && $ob->e_rights && ($ob->GetPermission('SECTION', $ob->arParams['item_id'], 'section_edit')) && CWebDavTools::isIntranetUser($USER->getId()))
{
	$rootSectionDataForGroup = CWebDavIblock::getRootSectionDataForGroup($ob->attributes['group_id']);
	$arResult['GROUP_DISK']['CONNECTED'] = !\Bitrix\Webdav\FolderInviteTable::getRow(array('filter' => array(
		'=INVITE_USER_ID' => $USER->getId(),
		'=USER_ID' => $USER->getId(),
		'=IS_APPROVED' => true,
		'=IBLOCK_ID' => $rootSectionDataForGroup['IBLOCK_ID'],
		'=SECTION_ID' => $rootSectionDataForGroup['SECTION_ID'],
	)));
	$arResult['GROUP_DISK']['CONNECT_URL'] = $APPLICATION->GetCurUri(http_build_query(array(
		'toWDController' => 1,
		'wdaction' => 'connect',
		'group' => $ob->attributes['group_id']
	)));
	$arResult['GROUP_DISK']['DISCONNECT_URL'] = $APPLICATION->GetCurUri(http_build_query(array(
		'toWDController' => 1,
		'wdaction' => 'disconnect',
		'group' => $ob->attributes['group_id']
	)));
	$arResult['GROUP_DISK']['DETAIL_URL'] = $APPLICATION->GetCurUri(http_build_query(array(
		'toWDController' => 1,
		'wdaction' => 'detail_group_connect',
		'group' => $ob->attributes['group_id']
	)));
}


$this->IncludeComponentTemplate();
if (!$ob->e_rights && $ob->CheckRight($arParams["PERMISSION"], "section_read") < "R")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}

return array("PERMISSION" => $arParams["PERMISSION"]);
?>
