<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;

if(!function_exists("__UnEscape"))
{
	function __UnEscape(&$item, $key)
	{
		if(is_array($item))
			array_walk($item, '__UnEscape');
		else
		{
			if(strpos($item, "%u") !== false)
				$item = $GLOBALS["APPLICATION"]->UnJSEscape($item);
			elseif (preg_match("/^.{1}/su", $item) == 1 && SITE_CHARSET != "UTF-8")
				$item = $GLOBALS["APPLICATION"]->ConvertCharset($item, "UTF-8", SITE_CHARSET);
		}
	}
}
if(!function_exists("__Escape"))
{
	function __Escape(&$item, $key)
	{
		if(is_array($item))
			array_walk($item, '__Escape');
		else
		{
			$item = $GLOBALS["APPLICATION"]->ConvertCharset($item, LANG_CHARSET, "UTF-8");
		}
	}
}
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["RESOURCE_TYPE"] = "FOLDER";
	if (!is_object($arParams["OBJECT"]))
		$arParams["OBJECT"] = new CWebDavFile($arParams, $arParams['BASE_URL']);
	$ob = $arParams["OBJECT"]; 
	$ob->IsDir(); 
	$arParams["SECTION_ID"] = $ob->arParams["item_id"];
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["ACTION"] = strToUpper(!empty($arParams["ACTION"]) ? $arParams["ACTION"] : $_REQUEST["ACTION"]);
	$arParams["ACTION"] = ($ob->arParams["not_found"] ? "ADD" : $arParams["ACTION"]); 
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#", 
		"section_edit" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#&ACTION=#ACTION#");
	
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
/***************** ADDITIONAL **************************************/
	$arParams["FORM_ID"] = "webdav_folder_edit"; 
/***************** STANDART ****************************************/
	if(!isset($arParams["CACHE_TIME"]))
		$arParams["CACHE_TIME"] = 3600;
	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
/********************************************************************
				/Input params
********************************************************************/
if ($arParams["PERMISSION"] < "W"):
	ShowError(GetMessage("WD_ERROR_ACCESS_DENIED"));
	return 0;
endif;

/********************************************************************
				Default params
********************************************************************/
$aMsg = array();
$bVarsFromForm = false;

$arResult["NAV_CHAIN"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), false);
$arResult["NAV_CHAIN_UTF8"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), true);
$arResult["SECTION"] = array("IBLOCK_SECTION_ID" => $arParams["SECTION_ID"]); 
if ($arParams["ACTION"] != "ADD")
{
	$arResult["SECTION"] = (is_array($ob->arParams["dir_array"]) ? $ob->arParams["dir_array"] : array());
	$arResult["SECTION"]["PATH"] = "/".implode("/", $arResult["NAV_CHAIN"]); 
}
$arResult["IBLOCK_SECTION"] = array();
$arResult["SECTION_LIST"] = array(); 
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				Actions
********************************************************************/
if (!empty($_REQUEST["cancel"]))
{
}
elseif (strToUpper($_REQUEST["edit_section"]) == "Y")
{
	array_walk($_REQUEST, '__UnEscape');
	
	$ob->IsDir(array("section_id" => $_REQUEST["IBLOCK_SECTION_ID"])); 
	$_REQUEST["IBLOCK_SECTION_ID"] = ($ob->arParams["not_found"] ? "" : $_REQUEST["IBLOCK_SECTION_ID"]); 
	$path = implode("/", $arResult["NAV_CHAIN"]);
	
	if (!check_bitrix_sessid())
	{
		$aMsg[] = array(
			"id" => "bad_sessid",
			"text" => GetMessage("WD_ERROR_BAD_SESSID"));
	}
	elseif (!in_array($arParams["ACTION"], array("DROP", "EDIT", "ADD", "UNDELETE")))
	{
		$aMsg[] = array(
			"id" => "bad_action",
			"text" => GetMessage("WD_ERROR_BAD_ACTION"));
	}
	elseif (in_array($arParams["ACTION"], array("DROP", "EDIT", "UNDELETE")) && empty($arParams["SECTION_ID"]))
	{
		$aMsg[] = array(
			"id" => "empty_section_id",
			"text" => GetMessage("WD_ERROR_EMPTY_SECTION_ID"));
	}
	elseif ($arParams["ACTION"] == "DROP")
	{
		$result = $ob->DELETE(array("section_id" => $arParams["SECTION_ID"])); 
		if (intVal($result) != 204) 
			$aMsg[] = array(
				"id" => "not_delete",
				"text" => GetMessage("WD_ERROR_DELETE"));
	}
	elseif ($arParams["ACTION"] == "UNDELETE")
	{
		$props = $ob->_get_props($arParams["SECTION_ID"]);
		$result = $ob->Undelete(array("section_id" => $arParams["SECTION_ID"], "dest_url" => $props["UNDELETEBX:"]["value"])); 
		if (intVal($result) != 204) 
			$aMsg[] = array(
				"id" => "not_delete",
				"text" => GetMessage("WD_ERROR_RECOVER"));
	}
	elseif (empty($_REQUEST["NAME"]) || strlen(trim($_REQUEST["NAME"])) < 1)
	{
		$aMsg[] = array(
			"id" => "empty_section_name",
			"text" => GetMessage("WD_ERROR_EMPTY_SECTION_NAME"));
	}
	elseif (!$ob->CheckName($_REQUEST["NAME"]))
	{
		$aMsg[] = array(
			"id" => "bad_section_name",
			"text" => GetMessage("WD_ERROR_BAD_SECTION_NAME"));
	}
	elseif ($arParams["ACTION"] == "ADD")
	{
		$_REQUEST["NAME"] = $ob->CorrectName($_REQUEST["NAME"]);
		$path = $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false); 
		$options = array("path" => $ob->GetIo()->CombinePath($path, $_REQUEST["NAME"])); 

		$ob->MKCOL($options); 
		$path = $options["path"]; 
	}
	else
	{
		if (isset($_REQUEST["IBLOCK_SECTION_ID"]))
		{
			$destUrl = str_replace(array("//", "/"), "/", $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false)."/".$_REQUEST["NAME"]."/"); 
		} else {
			$aPath = explode('/', $ob->_get_path($arParams["SECTION_ID"], false));
			$aPath[sizeof($aPath)-2] = $_REQUEST["NAME"];
			$destUrl = implode('/',$aPath);
		}
		$options = array(
			"path" => $ob->_get_path($arParams["SECTION_ID"], false), 
			"dest_url" => $destUrl
		);
		$ob->MOVE($options); 
		$path = trim($options["dest_url"], "/"); 
	}
	
	$oError = $APPLICATION->GetException();
	if ($oError):
		$aMsg[] = array(
			"id" => $arParams["ACTION"], 
			"text" => $oError->GetString());
	endif;

	if (empty($aMsg))
	{
		$path = ($arParams["CONVERT"] && $_REQUEST["AJAX_CALL"] != "Y" && SITE_CHARSET != "UTF-8" && $_REQUEST["popupWindow"] != "Y" ? 
			$APPLICATION->ConvertCharset($path, SITE_CHARSET, "UTF-8") : $path); 
		$url = str_replace("//", "", CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], array("PATH" => $path, "ACTION" => "EDIT")));

		if (empty($_REQUEST["apply"]))
		{
			$arNavChain = explode("/", $path); array_pop($arNavChain);
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)));
			if ($arParams['ACTION'] == "DROP")
				$url = WDAddPageParams($url, array("result"=>(($arParams["SECTION_ID"] == $ob->GetMetaID('TRASH')) ? "empty_trash" : "section_deleted")));
		}
		
		if ($_REQUEST["popupWindow"] == "Y")
		{
			$GLOBALS['APPLICATION']->RestartBuffer();
			require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
			$popupWindow = new CJSPopup('', ''); 
			$popupWindow->Close($bReload = true, (!empty($_REQUEST["back_url"]) ? $_REQUEST["back_url"] : $url));
			die(); 
		}
		elseif ($_REQUEST["AJAX_CALL"] != "Y" || !empty($_REQUEST["bxajaxid"]))
		{
			LocalRedirect($url);
		}
		else 
		{
			$APPLICATION->RestartBuffer();
			?><?=CUtil::PhpToJSObject(array("result" => strToLower($arParams["ACTION"]."ed"), "url" => $url));?><?
			die();
		}
	}
	else
	{
		$bVarsFromForm = true;
		$e = new CAdminException($aMsg);
		$GLOBALS["APPLICATION"]->ThrowException($e);
		$oError = $GLOBALS["APPLICATION"]->GetException();
		if ($oError)
			$arResult["ERROR_MESSAGE"] = $oError->GetString();
	}
}

/********************************************************************
				/Actions
********************************************************************/

/********************************************************************
				Data
********************************************************************/
if ($bVarsFromForm)
{
	$arResult["SECTION"]["~IBLOCK_SECTION_ID"] = $arResult["SECTION"]["IBLOCK_SECTION_ID"];
	$arResult["SECTION"]["IBLOCK_SECTION_ID"] = $_REQUEST["IBLOCK_SECTION_ID"];
	$arResult["SECTION"]["NAME"] = $_REQUEST["NAME"];
}
else 
{
	$_REQUEST["IBLOCK_SECTION_ID"] = $arResult["SECTION"]["IBLOCK_SECTION_ID"]; 
	$_REQUEST["NAME"] = $arResult["SECTION"]["NAME"];
}

$_REQUEST["IBLOCK_SECTION_ID"] = htmlspecialcharsbx($_REQUEST["IBLOCK_SECTION_ID"]);
$_REQUEST["NAME"] = htmlspecialcharsbx($_REQUEST["NAME"]);

foreach ($arResult["SECTION"] as $key => $val) 
{
	if (substr($key, 0, 1) == "~")
		continue; 
	elseif (!is_set($arResult["SECTION"], "~".$key))
		$arResult["SECTION"]["~".$key] = $val;

	$arResult["SECTION"][$key] = htmlspecialcharsEx($val);
}

$arResult["URL"] = array(
	"DELETE" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
			array("PATH" => $arResult["SECTION"]["PATH"], "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "DROP")), 
			array("edit_section" => "y", "sessid" => bitrix_sessid()), false)); 
/********************************************************************
				Data
********************************************************************/

$this->IncludeComponentTemplate();

/********************************************************************
				Standart operations
********************************************************************/
$arNavChain = $arResult["NAV_CHAIN"]; $res = array(); 
$sTitle = ($arParams["ACTION"] == "ADD" ? GetMessage("WD_NEW") : array_pop($arNavChain)); 
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle($sTitle);
}
if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	foreach ($arNavChain as $name)
	{
		$res[] = $ob->_uencode($name); 
		$GLOBALS["APPLICATION"]->AddChainItem(
			htmlspecialcharsEx($name), 
			CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $res))));
	}
	
	$GLOBALS["APPLICATION"]->AddChainItem($sTitle);
}
/********************************************************************
				/Standart operations
********************************************************************/
?>
