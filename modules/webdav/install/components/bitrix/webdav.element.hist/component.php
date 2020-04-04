<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("iblock")):
	ShowError(GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("workflow")):
	ShowError(GetMessage("W_WORKFLOW_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("bizproc")):
	return 0;
endif;
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["ELEMENT_ID"] = intVal($arParams["ELEMENT_ID"]);
	$arParams["PERMISSION"] = trim($arParams["PERMISSION"]);
	$arParams["CHECK_CREATOR"] = ($arParams["CHECK_CREATOR"] == "Y" ? "Y" : "N");
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y");
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#",
		"element" => "PAGE_NAME=element&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#",
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#",
		"element_history" => "PAGE_NAME=element_history&ELEMENT_ID=#ELEMENT_ID#",
		"element_history_get" => "PAGE_NAME=element_history_get&ELEMENT_ID=#ELEMENT_ID#&ELEMENT_NAME=#ELEMENT_NAME#",
		"user_view" => "PAGE_NAME=user_view&USER_ID=#USER_ID#");

	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH",
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action"));
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") === false);
	if (!$arParams["CONVERT_PATH"])
		$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#"));
	$arParams["CONVERT_PATH"] = (strToLower($arParams["CONVERT"]) == "full" ? true : $arParams["CONVERT_PATH"]);
/***************** ADDITIONAL **************************************/
	$arParams["USE_WORKFLOW"] = ((CModule::IncludeModule("workflow") &&
			(CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "WORKFLOW") != "N")) ? "Y" : "N");
	$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
	$arParams["PAGE_ELEMENTS"] = intVal(intVal($arParams["PAGE_ELEMENTS"]) > 0 ? $arParams["PAGE_ELEMENTS"] : 50);
	$arParams["PAGE_NAVIGATION_TEMPLATE"] = trim($arParams["PAGE_NAVIGATION_TEMPLATE"]);
	if (isset($arParams["OBJECT"]) && (! is_object($arParams["OBJECT"])))
		unset($arParams["OBJECT"]);
	$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/
if ($arParams["USE_WORKFLOW"] != "Y"):
	ShowError(GetMessage("WD_IBLOCK_WF_IS_NOT_ACTIVE"));
	return 0;
endif;
/********************************************************************
				Default params
********************************************************************/
if (empty($arParams["PERMISSION"])):
	if (CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "RIGHTS_MODE") !== "E")
		$arParams["PERMISSION"] = CIBlock::GetPermission($arParams["IBLOCK_ID"]);
	else
		$arParams["PERMISSION"] = 'X';
endif;
$arError = array();
$bVarsFromForm = false;
$arResult["SECTION"] = array();
$arResult["ELEMENT"] = array();
$arResult["NAV_CHAIN"] = array();
$arResult["NAV_CHAIN_PATH"] = array();
$arResult["USER"] = array();
$arResult["ERROR_MESSAGE"] = "";
$arResult['VERSIONS'] = array();
$arResult["VERSIONS_GRID"] = array();
$arUsersCache = array();
$arResult["WF_STATUSES"] = array();
$arResult["WF_STATUSES_PERMISSION"] = array();
/********************************************************************
				/Default params
********************************************************************/

if ($arParams["PERMISSION"] < "U"):
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
else:
	$db_res = CWorkflowStatus::GetDropDownList(($arParams["PERMISSION"] < "W" ? "N" : "Y"),  "desc");
	if ($db_res && $res = $db_res->Fetch())
	{
		do
		{
			$arResult["WF_STATUSES"][intVal($res["REFERENCE_ID"])] = $res["REFERENCE"];
			$arResult["WF_STATUSES_PERMISSION"][intVal($res["REFERENCE_ID"])] = ($arParams["PERMISSION"] < "W" ?
				CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]) : 2);
		}while ($res = $db_res->Fetch());
	}

	if (empty($arResult["WF_STATUSES"])):
		ShowError(GetMessage("WD_ACCESS_DENIED"));
		return 0;
	endif;
endif;
/********************************************************************
				Data
********************************************************************/
/************** Element ********************************************/
$arFilter = array(
	"ID" => $arParams["ELEMENT_ID"],
	"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	"IBLOCK_ACTIVE" => "Y",
	"SHOW_HISTORY" => "Y"
);

$db_res = CIBlockElement::GetList(array(), $arFilter);
if (!($db_res && $res = $db_res->GetNext()))
{
	ShowError(GetMessage("WD_ERROR_ELEMENT_NOT_FOUND"));
	return 0;
}
elseif(
	$arParams["CHECK_CREATOR"] == "Y"
	&& $res["CREATED_BY"] != $GLOBALS['USER']->GetId()
)
{
		ShowError(GetMessage("WD_ACCESS_DENIED"));
		return 0;
}

$res["FILE_EXTENTION"] = strtolower(strrchr($res['NAME'] , '.'));
$res["~WF_STATUS_TITLE"] = CIBlockElement::WF_GetStatusTitle($res["WF_STATUS_ID"]);
$res["WF_STATUS_TITLE"] = htmlspecialcharsEx($res["~WF_STATUS_TITLE"]);

/************** Paths **********************************************/
$res["URL"] = array(
	"DOWNLOAD" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_HISTORY_GET_URL"],
			array("ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["ELEMENT_NAME"])),
	"~DOWNLOAD" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_GET_URL"],
			array("ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["ELEMENT_NAME"])),
	"VIEW" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_URL"],
			array("ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["ELEMENT_NAME"])),
	"~VIEW" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"],
			array("ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["ELEMENT_NAME"])),
	"EDIT" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"],
			array("ELEMENT_ID" => $res["ID"], "ACTION" => "EDIT")),
	"~EDIT" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
			array("ELEMENT_ID" => $res["ID"], "ACTION" => "EDIT")),
	"DELETE" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"],
			array("ELEMENT_ID" => $res["ID"], "ACTION" => "DELETE")),
	"~DELETE" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"],
			array("ELEMENT_ID" => $res["ID"], "ACTION" => "DELETE")));
$res["URL"]["DELETE"] = WDAddPageParams($res["URL"]["DELETE"], array("edit" => "y", "sessid" => bitrix_sessid(), 'back_url'=>urlencode($APPLICATION->GetCurPageParam())));
$res["URL"]["~DELETE"] = WDAddPageParams($res["URL"]["~DELETE"], array("edit" => "y", "sessid" => bitrix_sessid(), 'back_url'=>urlencode($APPLICATION->GetCurPageParam())));

/************** Permission *****************************************/
$arResult["ELEMENT"] = $res;

$arResult["ELEMENT"]["PERMISSION"] = CIBlockDocumentWebdav::GetIBRights('ELEMENT', $arParams["IBLOCK_ID"], $arParams["ELEMENT_ID"]);

if (CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_read") < "R")
{
	ShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}


$res = array(
	"UNLOCK" => "N",
	"EDIT" => (CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_edit") >= "W" ? "Y" : "N"),
	"DELETE" => (CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_edit") >= "W" ? "Y" : "N"),
	"HISTORY" => "Y"
);

if (
	$arResult["ELEMENT"]["LOCK_STATUS"] == "yellow"
	|| (
		$arResult["ELEMENT"]["LOCK_STATUS"] == "red"
		&&
		(
			CWorkflow::IsAdmin()
			|| ($USER->CanDoOperation('webdav_change_settings'))
		)
	)
)
{
	$res["UNLOCK"] = "Y";
}

if ($arResult["ELEMENT"]["LOCK_STATUS"] == "red")
{
	$res["EDIT"] = "N";
}
elseif (CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_bizproc_start") == "U")
{
	$res["EDIT"] = (
		($arResult["ELEMENT"]["WF_STATUS_ID"] > 1
		&& $arResult["WF_STATUSES_PERMISSION"][$arResult["ELEMENT"]["WF_STATUS_ID"]] < 2)
		? "N"
		: "Y"
	);
}

$arResult["ELEMENT"]["SHOW"] = $res;

/************** Last element ***************************************/
$arResult["ELEMENT"]["LAST"] = $arResult["ELEMENT"];
$LAST_ID = CIBlockElement::WF_GetLast($arResult["ELEMENT"]["ID"]);
if ($LAST_ID != $arResult["ELEMENT"]["ID"])
{
	$db_res = CIBlockElement::GetByID($LAST_ID);
	if ($db_res && $res = $db_res->Fetch())
		$arResult["ELEMENT"]["LAST"] = $res;
}

/************** Versions *******************************************/
$db_res = CIBlockElement::WF_GetHistoryList($arParams['ELEMENT_ID'], $by = 's_id', $order = 'desc',
	array("IBLOCK_ID" => $arParams['IBLOCK_ID']), $is_filtered);
if ($db_res)
{
	$db_res->NavStart($arParams["PAGE_ELEMENTS"]);
	$arResult["NAV_RESULT"] = $db_res;
	$arResult["NAV_STRING"] = $db_res->GetPageNavStringEx($navComponentObject, GetMessage("WD_DOCUMENTS"), $arParams["PAGE_NAVIGATION_TEMPLATE"]);

	if ($this->__parent)
		$this->__parent->arResult["HISTORY_LENGTH"] = $db_res->NavRecordCount;

	$nameTemplate = $arParams['NAME_TEMPLATE'];
	if (empty($nameTemplate))
		$nameTemplate = CSite::GetNameFormat();

	if ($res = $db_res->GetNext())
	{
		do
		{
			if (isset($arParams["OBJECT"]))
			{
				$dbPropRes = CIBlockElement::GetProperty($arParams["IBLOCK_ID"], $res["ID"], "sort", "asc", array("CODE" => $arParams["OBJECT"]->file_prop));
				if ($dbPropRes && ($arPropRes = $dbPropRes->GetNext()))
				{
					$arFile = CFile::MakeFileArray($arPropRes["VALUE"]);
					$arFile["FILE_SIZE"] = $arFile["size"];
					__parse_file_size($arFile, $res);
				}
			}
			$res["~WF_STATUS_TITLE"] = CIBlockElement::WF_GetStatusTitle($res["WF_STATUS_ID"]);
			$res["WF_STATUS_TITLE"] = htmlspecialcharsEx($res["~WF_STATUS_TITLE"]);
			$res["SHOW"] = array(
				"RESTORE" => ((CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_delete") > "W") ? "Y" : "N"),
				"DELETE" => ((CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_delete") > "W") ? "Y" : "N"));
			if (CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_edit") <= "W")
			{
				if ($arResult["WF_STATUSES_PERMISSION"][$arResult["ELEMENT"]["LAST"]["WF_STATUS_ID"]] >= 2 &&
					$arResult["WF_STATUSES_PERMISSION"][$res["WF_STATUS_ID"]] >= 1)
				{
					$res["SHOW"]["RESTORE"] = "Y";
				}
				if ($arResult["WF_STATUSES_PERMISSION"][$res["WF_STATUS_ID"]] >= 2)
				{
					$res["SHOW"]["DELETE"] = "Y";
				}
			}

			if ($res["MODIFIED_BY"] > 0)
			{
				if(!array_key_exists($res["MODIFIED_BY"], $arUsersCache))
				{
					$rsUser = CUser::GetByID($res["MODIFIED_BY"]);
					$arUsersCache[$res["MODIFIED_BY"]] = $rsUser->GetNext();
				}
				$arUsersCache[$res["MODIFIED_BY"]]["URL"] = CComponentEngine::MakePathFromTemplate($arParams["USER_VIEW_URL"],
					array("USER_ID" => $res['MODIFIED_BY']));
			}

			$res['URL']['DOWNLOAD'] = CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_HISTORY_GET_URL"],
				array("ELEMENT_ID" => $res['ID'], "ELEMENT_NAME" => $res['NAME']));
			$res['URL']['~DOWNLOAD'] = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_GET_URL"],
				array("ELEMENT_ID" => $res['ID'], "ELEMENT_NAME" => $res['NAME']));
			$res['URL']['RESTORE'] = CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_HISTORY_URL"],
				array("ELEMENT_ID" => $arParams["ELEMENT_ID"]));
			$res['URL']['~RESTORE'] = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_URL"],
				array("ELEMENT_ID" => $arParams["ELEMENT_ID"]));

			$back_url = urlencode($APPLICATION->GetCurPageParam());

			$res["URL"]["RESTORE"] = WDAddPageParams($res["URL"]["RESTORE"],
				array("history_id" => $res["ID"], "action" => "restore", "edit" => "y", "sessid" => bitrix_sessid(), 'back_url' => $back_url));
			$res["URL"]["~RESTORE"] = WDAddPageParams($res["URL"]["~RESTORE"],
				array("history_id" => $res["ID"], "action" => "restore", "edit" => "y", "sessid" => bitrix_sessid(), 'back_url' => $back_url));

			$res["URL"]["DELETE"] = WDAddPageParams($res["URL"]["RESTORE"],
				array("history_id" => $res["ID"], "action" => "delete", "edit" => "y", "sessid" => bitrix_sessid(), 'back_url' => $back_url));
			$res["URL"]["~DELETE"] = WDAddPageParams($res["URL"]["~RESTORE"],
				array("history_id" => $res["ID"], "action" => "delete", "edit" => "y", "sessid" => bitrix_sessid(), 'back_url' => $back_url));
			$res['URL']['~VIEW'] = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"],
				array("ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["NAME"]));
			$res['URL']['VIEW'] = CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_URL"],
				array("ELEMENT_ID" => $res["ID"], "ELEMENT_NAME" => $res["NAME"]));

			$arResult['VERSIONS'][$res["ID"]] = $res;
			$arActions = array(
				array(
					"ICONCLASS" => "element_view",
					"TITLE" => GetMessage("WD_VIEW_ELEMENT"),
					"TEXT" => GetMessage("WD_VIEW"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~VIEW"])."');"),
				array(
					"ICONCLASS" => "element_download",
					"TITLE" => GetMessage("WD_DOWNLOAD_ELEMENT"),
					"TEXT" => GetMessage("WD_DOWNLOAD"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DOWNLOAD"])."');"));
			if ($res["SHOW"]["RESTORE"] == "Y")
			{
				$arActions[] = array(
					"ICONCLASS" => "restore_element",
					"TITLE" => GetMessage("WD_RESTORE_ELEMENT"),
					"TEXT" => GetMessage("WD_RESTORE"),
					"ONCLICK" => "jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~RESTORE"])."');");
			}
			if ($res["SHOW"]["DELETE"] == "Y")
			{
				$arActions[] = array(
					"ICONCLASS" => "element_delete",
					"TITLE" => GetMessage("WD_DELETE_ELEMENT"),
					"TEXT" => GetMessage("WD_DELETE"),
					"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage("WD_DELETE_CONFIRM"))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res["URL"]["~DELETE"])."')};");
			}
			$maxLength = 20;
			$aCols = array(
				"NAME" => '<a target="_blank" href="'.$res['URL']['DOWNLOAD'].'">'.$res['NAME'].'</a>',
				"WF_STATUS_ID" => '['.$res["WF_STATUS_ID"].'] '.$res["WF_STATUS_TITLE"],
				"WF_COMMENTS" => (strLen($res["~WF_COMMENTS"]) <= $maxLength ?
					$res["WF_COMMENTS"]
					:
					htmlspecialcharsEx(substr($res["~WF_COMMENTS"], 0, $maxLength - 3))."...".ShowJSHint($res["WF_COMMENTS"], array('return' => true))
				),
				"MODIFIED_BY" => (empty($arUsersCache[$res["MODIFIED_BY"]]) ?
					$res["USER_NAME"]
					:
					'[<a href="'.$arUsersCache[$res["MODIFIED_BY"]]["URL"].'">'.$res["MODIFIED_BY"].'</a>] ('.
						$arUsersCache[$res["MODIFIED_BY"]]["LOGIN"].") ".CUser::FormatName($nameTemplate, $arUsersCache[$res["MODIFIED_BY"]], true, false)
				));

			$res['TIMESTAMP_X'] = FormatDateFromDB($res['TIMESTAMP_X']);
			$arResult["VERSIONS_GRID"][$res["ID"]] = array(
				"id" => $res["ID"],
				"data" => $res,
				"actions" => $arActions,
				"columns" => $aCols,
				"editable" => true);
		} while ($res = $db_res->GetNext());
	}
}
$arResult["USERS"] = $arUsersCache;
/************** Navigation *****************************************/
$arParams["SECTION_ID"] = intVal($arResult["ELEMENT"]["IBLOCK_SECTION_ID"]);
$bRootFounded = (empty($arResult["ROOT_SECTION"]) ? true : false);
if ($arParams["SECTION_ID"] > 0)
{
	$db_res = CIBlockSection::GetNavChain($arParams['IBLOCK_ID'], $arParams["SECTION_ID"]);
	if ($db_res && ($res = $db_res->Fetch()) && intVal($res["ID"]) > 0)
	{
		do
		{
			if ($res["ID"] == $arParams["ROOT_SECTION_ID"])
			{
				$bRootFounded = true;
				continue;
			}
			if (!$bRootFounded)
				continue;
			$arResult["NAV_CHAIN"][] = $res;
			$arResult["NAV_CHAIN_PATH"][] = CWebDavIblock::_uencode($res["NAME"],
				array("utf8" => "Y", "convert" => ($arParams["CONVERT_PATH"] == true ? "full" : "allowed")));
		}
		while ($res = $db_res->Fetch());
	}
}
/************** Path ***********************************************/
$path = $arResult["NAV_CHAIN_PATH"];
$path[] = CWebDavIblock::_uencode($arResult["ELEMENT"]["NAME"], array("utf8" => "Y", "convert" => "allowed"));
$arResult["ELEMENT"]["URL"]["THIS"] = CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"],
	array("PATH" => implode("/", $path)));
/********************************************************************
				/Data
********************************************************************/

/********************************************************************
				Action
********************************************************************/
if ((!empty($_POST["HISTORY_ID"]) || !empty($_GET["history_id"])) && check_bitrix_sessid())
{
	$arID = (!empty($_POST["HISTORY_ID"]) ? $_POST["HISTORY_ID"] : $_GET["history_id"]);
	$arID = (is_array($arID) ? $arID : array($arID));
	$ob = new CWebDavIblock($arParams['IBLOCK_ID']);

	foreach($arID as $ID)
	{
		if(strlen($ID)<=0 || empty($arResult['VERSIONS'][$ID]))
			continue;
		$ID = IntVal($ID);

		$d = CIBlockElement::GetByID($ID);
		if($dr = $d->Fetch())
		{
			if ($_REQUEST["action"] == "restore")
			{
				$DB->StartTransaction();
				if(!CIBlockElement::WF_Restore($ID))
				{
					$DB->Rollback();
				}
				else
				{
					$options = array("element_id" => $arParams["ELEMENT_ID"]);
					$ob->UNLOCK($options);
					$DB->Commit();
				}
			}
			elseif(strlen($dr["WF_PARENT_ELEMENT_ID"])>0)
			{
				if ((CWebDavIblock::CheckRight($arResult["ELEMENT"]["PERMISSION"], "element_edit") < "W")
					&& !(
						($dr["WF_STATUS_ID"] > 1)
						&& $arResult["WF_STATUSES_PERMISSION"][$dr["WF_STATUS_ID"]] >= 2
					)
				)
				{
					$DB->StartTransaction();

					if(!CIBlockElement::Delete(intval($ID)))
						$DB->Rollback();
					else
						$DB->Commit();
				}
			}
		}
	}
	$url = isset($_REQUEST['back_url']) ? $_REQUEST['back_url'] :
		CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_HISTORY_URL"], array("ELEMENT_ID" => $arParams["ELEMENT_ID"]));
	LocalRedirect($url);
}
/********************************************************************
				/Action
********************************************************************/

$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_TITLE")." ". $arResult["ELEMENT"]["NAME"]);
}

if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	reset($arResult["NAV_CHAIN_PATH"]);
	$arNavChain = array(current($arResult["NAV_CHAIN_PATH"]));
	foreach ($arResult["NAV_CHAIN"] as $res)
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"],
			array("PATH" => implode("/", $arNavChain)));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($res["NAME"]), $url);
		$arNavChain[] = next($arResult["NAV_CHAIN_PATH"]);
	}
	$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($arResult["ELEMENT"]["NAME"]));
}
if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/
?>
