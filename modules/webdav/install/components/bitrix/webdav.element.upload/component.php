<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	__WDShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;

if (!function_exists("__WDShowError"))
{
	function __WDShowError($sError)
	{
		if (isset($_REQUEST["use_light_view"]) || isset($_REQUEST["use_hidden_view"]))
		{
			$GLOBALS['APPLICATION']->RestartBuffer();
			require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
			$popupWindow = new CJSPopup('', '');
			$popupWindow->ShowTitlebar(GetMessage("WD_UPLOAD_ERROR_TITLE"));
			$popupWindow->StartContent();
		}
		if (strpos($sError, "<") > 0)
			echo "<p><font class=\"errortext\">".$sError."</font></p>\n";
		else
			ShowError($sError);
		if (isset($_REQUEST["use_light_view"]) || isset($_REQUEST["use_hidden_view"])) 
		{
			$popupWindow->ShowStandardButtons(array("close"));
			die();
		}
	}
}

if(!function_exists("__UnEscape"))
{
	function __UnEscape(&$item, $key)
	{
		if (is_array($item))
			array_walk($item, '__UnEscape');
		elseif (preg_match("/^.{1}/su", $item) == 1)
			$item = $GLOBALS["APPLICATION"]->ConvertCharset($item, "UTF-8", SITE_CHARSET);
	}
}
if(!function_exists("__Escape"))
{
	function __Escape(&$item, $key)
	{
		if (is_array($item))
			array_walk($item, '__Escape');
		else
			$item = $GLOBALS["APPLICATION"]->ConvertCharset($item, SITE_CHARSET, "UTF-8");
	}
}
if(!function_exists("__CorrectFileName"))
{
	function __CorrectFileName(&$arFiles)
	{
		foreach ($arFiles as $key => $val):
			if (strpos($key, "SourceFile_") === false || (strpos($val["name"], "/") === false && strpos($val["name"], "\\") === false))
				continue;
			$tmp = array();
			if (strpos($val["name"], "/") !== false):
				$tmp = explode("/", $val["name"]);
			elseif (strpos($val["name"], "\\") !== false):
				$tmp = explode("\\", $val["name"]);
			endif;
			if (!empty($tmp)):
				$tmp = array_reverse($tmp);
				foreach ($tmp as $res):
					if (!empty($res)):
						$arFiles[$key]["name"] = $res;
						break;
					endif;
				endforeach;
			endif;
		endforeach;
	}
}

/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	if (is_object($arParams["OBJECT"]))
		$arParams["RESOURCE_TYPE"] = strtoupper($arParams["OBJECT"]->Type);
	$arParams["RESOURCE_TYPE"] = ($arParams["RESOURCE_TYPE"] == "FOLDER" ? "FOLDER" : "IBLOCK");
	if (!is_object($arParams["OBJECT"]))
	{
		if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
			$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
		else
			$arParams["OBJECT"] = new CWebDavFile($arParams, $arParams['BASE_URL']);
	}
	$ob = $arParams["OBJECT"];
	if ($arParams["RESOURCE_TYPE"] == "IBLOCK")
	{
		if (isset($_REQUEST['random_folder']) && $_REQUEST['random_folder']==='Y')
			$arParams['SECTION_ID'] = $ob->GetMetaID('DROPPED');
		$arParams["IBLOCK_TYPE"] = $ob->IBLOCK_TYPE;
		$arParams["IBLOCK_ID"] = $ob->IBLOCK_ID;
		$arParams["ROOT_SECTION_ID"] = ($ob->arRootSection ? $ob->arRootSection["ID"] : 0);
		$arParams["SECTION_ID"] = intVal($arParams["SECTION_ID"] > 0 ? $arParams["SECTION_ID"] : $_REQUEST["SECTION_ID"]);
		$arParams["CHECK_CREATOR"] = ($arParams["OBJECT"]->check_creator ? "Y" : "N");
		$arParams["USE_COMMENTS"] = ($arParams["USE_COMMENTS"] == "Y" && IsModuleInstalled("forum") ? "Y" : "N");
		$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);
		$arResult['USER_FIELDS'] = $ob->GetUfFields();
	}
	else
	{
		$arParams["FOLDER"] = $ob->real_path;
		$arParams["USE_COMMENTS"] = "N";
		$arParams["FORUM_ID"] = 0;
		$arParams["SECTION_ID"] = trim($ob->_path);
		$ob->IsDir();
		if ($ob->arParams['is_dir'])
			$arParams['SECTION_ID'] = $ob->arParams['item_id'];
		else
			$arParams['SECTION_ID'] = $ob->arParams['parent_id'];
		if (empty($arParams['SECTION_ID']))
			$arParams['SECTION_ID'] = '/';

	}

	if (
		isset($_REQUEST['random_folder']) &&
		($_REQUEST['random_folder'] == 'Y') &&
		($arParams["RESOURCE_TYPE"] == "IBLOCK") &&
		(intval($arParams['SECTION_ID']) <= 0)
	)
	{
		$arParams['SECTION_ID'] = $ob->GetMetaID('DROPPED');
	}

	$ob->IsDir(array("section_id" => $arParams["SECTION_ID"]));

	$arParams["PERMISSION"] = $ob->permission;
	$arParams["REPLACE_SYMBOLS"] = ($arParams["REPLACE_SYMBOLS"] == "Y" ? "Y" : "N");
	$arParams["NAME_FILE_PROPERTY"] = strToupper(trim(empty($arParams["NAME_FILE_PROPERTY"]) ? "FILE" : $arParams["NAME_FILE_PROPERTY"]));
	$arParams["PATH_TO_TMP"] = preg_replace("'[\\\\/]+'", "/", $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/tmp/uploader/");
	CheckDirPath($arParams["PATH_TO_TMP"]);

	$arParams["IMAGE_UPLOADER_ACTIVEX_CLSID"] = "718B3D1E-FF0C-4EE6-9F3B-0166A5D1C1B9";
	$arParams["IMAGE_UPLOADER_ACTIVEX_CONTROL_VERSION"] = "5,7,26,0";
	$arParams["IMAGE_UPLOADER_JAVAAPPLET_VERSION"] = "5.7.26.0";

	$arParams["THUMBNAIL_ACTIVEX_CLSID"] = "58C8ACD5-D8A6-4AC8-9494-2E6CCF6DD2F8";
	$arParams["THUMBNAIL_ACTIVEX_CONTROL_VERSION"] = "3,5,204,0";
	$arParams["THUMBNAIL_JAVAAPPLET_VERSION"] = "1.1.81.0";

	$arParams["ELEMENT_ID"] = (isset($_REQUEST['update_document']) ? urldecode($_REQUEST['update_document']) : 0);
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#", 
		"element_edit" => "PAGE_NAME=element_edit&PATH=#PATH#", 
		"element_upload" => "PAGE_NAME=sections&PATH=#PATH#");
	
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		$arParams[strToUpper($URL)."_URL"] = trim($arParams[strToUpper($URL)."_URL"]);
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $APPLICATION->GetCurPage()."?".$URL_VALUE;
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
	$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") === false);
	if (!$arParams["CONVERT_PATH"])
		$arParams["CONVERT_PATH"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#"));
	$arParams["CONVERT_PATH"] = (strToLower($arParams["CONVERT"]) == "full" ? true : $arParams["CONVERT_PATH"]);
/***************** ADDITIONAL **************************************/
	$arParams["WORKFLOW"] = (!$ob->workflow ? "N" : $ob->workflow); 
	$arParams["DOCUMENT_ID"] = $arParams["DOCUMENT_TYPE"] = $arParams["OBJECT"]->wfParams["DOCUMENT_TYPE"];
	$arParams["DOCUMENT_ID"][2] = 0;

	$arParams["UPLOAD_MAX_FILE"] = intVal(!empty($arParams["UPLOAD_MAX_FILE"]) ? $arParams["UPLOAD_MAX_FILE"] : 1);
	$arParams["UPLOAD_MAX_FILE"] = 1;

	$iUploadMaxFilesize = CUtil::Unformat(ini_get('upload_max_filesize'));
	$iPostMaxSize = CUtil::Unformat(ini_get('post_max_size'));
	$arParams["UPLOAD_MAX_FILESIZE"] = intVal($arParams["UPLOAD_MAX_FILESIZE"]);
	if ($arParams["UPLOAD_MAX_FILESIZE"] > 0)
		$arParams["UPLOAD_MAX_FILESIZE"] = min($iUploadMaxFilesize, $iPostMaxSize, $arParams["UPLOAD_MAX_FILESIZE"]);
	else 
		$arParams["UPLOAD_MAX_FILESIZE"] = min($iUploadMaxFilesize, $iPostMaxSize);
	$arParams["UPLOAD_MAX_FILESIZE_BYTE"] = $arParams["UPLOAD_MAX_FILESIZE"]*1024*1024;
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["DISPLAY_PANEL"] = ($arParams["DISPLAY_PANEL"]=="Y"); //Turn off by default
/********************************************************************
				/Input params
********************************************************************/

if ($ob->arParams["not_found"] == true)
{
	__WDShowError(GetMessage("WD_ERROR_BAD_SECTION"));
	if ($arParams["SET_STATUS_404"] == "Y"):
		CHTTP::SetStatus("404 Not Found");
	endif;
	return 0;
}
if (!empty($ob->Error))
{
	$e = new CAdminException($ob->Error);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	$err = $GLOBALS["APPLICATION"]->GetException(); 
	if ($err):
		__WDShowError($err->GetString());
	endif;
	return 0;
}
elseif ($ob->CheckRight($arParams["PERMISSION"], "section_element_bind") < "U")
{
	__WDShowError(GetMessage("WD_ACCESS_DENIED"));
	return 0;
}

if ($arParams['ELEMENT_ID'] !== 0)
{
	if ($ob->Type == 'iblock')
	{
		$arFilter = array(
			"ID" => $arParams["ELEMENT_ID"],
			"IBLOCK_ACTIVE" => "Y",
			"SHOW_HISTORY" => "Y");
		$db_res = CIBlockElement::GetList(array(), $arFilter);
		if (!($db_res && $res = $db_res->GetNext())):
			__WDShowError(GetMessage("WD_ERROR_ELEMENT_NOT_FOUND"));
			return 0;
		elseif($arParams["CHECK_CREATOR"] == "Y" && $res["CREATED_BY"] != $GLOBALS['USER']->GetId()):
			__WDShowError(GetMessage("WD_ACCESS_DENIED"));
			return 0;
		endif;
		$res["FILE_EXTENTION"] = strtolower(strrchr($res['NAME'] , '.'));
		$arResult['ELEMENT'] = $res;
	}
	elseif ($ob->Type == 'folder')
	{
		if ($ob->GetIo()->FileExists($ob->GetIo()->CombinePath($ob->real_path_full, $arParams["ELEMENT_ID"])))
		{
			$arResult['ELEMENT']["FILE_EXTENTION"] = strtolower(strrchr($arParams["ELEMENT_ID"] , '.'));
		}
		else
		{
			__WDShowError(GetMessage("WD_ERROR_ELEMENT_NOT_FOUND"));
			return 0;
		}
	}
}
/********************************************************************
				Default params
********************************************************************/
$cache = new CPHPCache;
$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName."/".$arParams["IBLOCK_ID"]."/");

$bVarsFromForm = false;
$arResult["SECTION"] = $ob->arParams["dir_array"];
$arResult["NAV_CHAIN"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), false);
$arResult["URL"] = array(
	"VIEW" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_URL"], array("ELEMENT_ID" => $arParams["ELEMENT_ID"], "ELEMENT_NAME" => $res["ELEMENT_NAME"])),
	"~SECTIONS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
	"SECTIONS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
	"EDIT" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], array("ACTION" => "EDIT", "PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
	"~UPLOAD" => CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_UPLOAD_URL"], array("SECTION_ID" => $arParams["SECTION_ID"])), 
	"UPLOAD" => CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_UPLOAD_URL"],array("SECTION_ID" => $arParams["SECTION_ID"])));
$arResult["ROOT_SECTION"] = $ob->arRootSection;
$arResult["RETURN_DATA"] = array();
$arResult["WF_STATUSES"] = array();
$arResult["WF_STATUSES_PERMISSION"] = array();
$arParams["WORKFLOW"] = $arWorkFlow = array(
	"LAST_ID" => $arParams["ELEMENT_ID"], 
	"STATUS_ID" => 0, 
	"STATUS_TITLE" => "", 
	"STATUS_PERMISSION" => "");
$arDocumentStates = array();
$arResult["CurrentUserGroups"] = array_merge(array("Author"), $ob->USER["GROUPS"]); 
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				Data
********************************************************************/
if ($ob->workflow == "workflow")
{
	if ($ob->e_rights)
	{
		$arSectionRights = $ob->GetPermission('SECTION', $arParams['SECTION_ID']);
		$perms = (isset($arSectionRights['section_rights_edit']) ? 'Y' : 'N');
	}
	else
	{
		$perms = ($arParams["PERMISSION"] < "W" ? "N" : "Y");
	}
	$db_res = CWorkflowStatus::GetDropDownList($perms);
	$iEditStatus = 0;
	if ($db_res && $res = $db_res->Fetch())
	{
		do 
		{
			$arResult["WF_STATUSES"][intVal($res["REFERENCE_ID"])] = $res["REFERENCE"];
			$arResult["WF_STATUSES_PERMISSION"][intVal($res["REFERENCE_ID"])] = ($perms < "W" ? 
				CIBlockElement::WF_GetStatusPermission($res["REFERENCE_ID"]) : 2);
			if ($arResult["WF_STATUSES_PERMISSION"][intVal($res["REFERENCE_ID"])] == 2)
				$iEditStatus = intVal($res["REFERENCE_ID"]);
		}while ($res = $db_res->Fetch());
	}
	
	if (empty($arResult["WF_STATUSES"])):
		__WDShowError(GetMessage("WD_ACCESS_DENIED"));
		return 0;
	elseif (empty($_REQUEST["WF_STATUS_ID"]) && $iEditStatus > 0): 
		if (array_key_exists(1, $arResult["WF_STATUSES"]))
			$_REQUEST["WF_STATUS_ID"] = 1;
		else 
			$_REQUEST["WF_STATUS_ID"] = $iEditStatus;
	endif;
}
elseif ($ob->workflow == "bizproc")
{
	$docID = null;
	if ( !empty($arParams['ELEMENT_ID']) )
	{
		$docID = $arParams['DOCUMENT_TYPE'];
		$docID[2] = intval($arParams['ELEMENT_ID']);
	}

	$arDocumentStates = CBPDocument::GetDocumentStates(
		$arParams["DOCUMENT_TYPE"],
		$docID);

	$arResult['DOCUMENT_STATES'] = $arDocumentStates;

	$canWrite = CBPDocument::CanUserOperateDocumentType(
		CBPCanUserOperateOperation::WriteDocument,
		$GLOBALS["USER"]->GetID(),
		$arParams["DOCUMENT_TYPE"],
		array(
			"SectionId" => $arParams["SECTION_ID"],
			"AllUserGroups" => $arResult["CurrentUserGroups"],
			"DocumentStates" => $arDocumentStates)
		);

	if (!$canWrite)
	{
		$arFilter = array("DOCUMENT_TYPE" => $ob->wfParams['DOCUMENT_TYPE'], "ACTIVE"=>"Y");

		$dbWFTemplates = CBPWorkflowTemplateLoader::GetList(
			array(),
			$arFilter,
			false,
			false,
			array("ID"));

		if ($dbWFTemplates && $arWFTemplates = $dbWFTemplates->Fetch())
		{
			if (empty($arDocumentStates) && $arParams['PERMISSION']==='U')
				$sErrMsg = GetMessage("WD_NO_BP_AUTORUN");
			elseif (!empty($arDocumentStates) && $arParams['PERMISSION']==='U')
				$sErrMsg = GetMessage("WD_BP_ACTIVE_STATES");
			else
				$sErrMsg = GetMessage("WD_ACCESS_DENIED");
		}
		else
			$sErrMsg = GetMessage("WD_NO_BP_TEMPLATES");

		if (!$ob->e_rights && $arParams["PERMISSION"] >= "X")
			$sErrMsg .= GetMessage("WD_NO_BP_TEMPLATES_ADMIN", array("#LINK#" => $arParams["WEBDAV_BIZPROC_WORKFLOW_ADMIN_URL"]));

		__WDShowError($sErrMsg);
		return 0;
	}
}
/********************************************************************
				/Data
********************************************************************/

/********************************************************************
				Data for custom
********************************************************************/
$arParams["USE_BIZPROC"] = ($ob->workflow == "bizproc" ? "Y" : "N"); 
$arParams["USE_WORKFLOW"] = ($ob->workflow == "workflow" ? "Y" : "N"); 
$arParams["BIZPROC"] = array(
	"MODULE_ID" => "webdav", 
	"ENTITY" => $ob->wfParams["DOCUMENT_TYPE"][1], 
	"DOCUMENT_TYPE" => (empty($arParams["BIZPROC"]["DOCUMENT_TYPE"]) ? $ob->wfParams["DOCUMENT_TYPE"][2] : $arParams["BIZPROC"]["DOCUMENT_TYPE"]));
/********************************************************************
				/Data for custom
********************************************************************/

/********************************************************************
				Actions
********************************************************************/
$GLOBALS["APPLICATION"]->ResetException();
if ($ob->Type == "iblock")
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action_iblock.php");
else
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action_file.php");
$result = include($path);
if ($result !== true)
{
	$oError = $GLOBALS["APPLICATION"]->GetException();
	if ($oError)
	{
		$arResult["ERROR_MESSAGE"] = $oError->GetString();
	}
}
/********************************************************************
				Actions
********************************************************************/

if (isset($_REQUEST['use_hidden_view']) && ($_REQUEST['use_hidden_view'] == 'Y'))
	$this->__templateName = 'hidden';
$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_TITLE"));
}

if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	$res = array(); 
	foreach ($arResult["NAV_CHAIN"] as $name)
	{
		$res[] = $ob->_uencode($name); 
		$GLOBALS["APPLICATION"]->AddChainItem(
			htmlspecialcharsEx($name), 
			CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
				array("PATH" => implode("/", $res))));
	}
	$GLOBALS["APPLICATION"]->AddChainItem(GetMessage("WD_TITLE"));
}

if ($arParams["RESOURCE_TYPE"] == "IBLOCK" && $arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/

if ($_REQUEST["FORMAT_ANSWER"] == "return")
{
	return $arResult["RETURN_DATA"];
}
?>
