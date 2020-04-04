<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
elseif (!CModule::IncludeModule("iblock")):
	ShowError(GetMessage("W_IBLOCK_IS_NOT_INSTALLED"));
	return 0;
endif;

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");

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
		$arParams["OBJECT"] = new CWebDavIblock($arParams['IBLOCK_ID'], $arParams['BASE_URL'], $arParams);
	$ob = $arParams["OBJECT"]; 
	$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
	$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
	$arParams["ROOT_SECTION_ID"] = intVal($arParams["ROOT_SECTION_ID"]);
	$arParams["SECTION_ID"] = intVal(!empty($arParams["SECTION_ID"]) ? $arParams["SECTION_ID"] : $_REQUEST["SECTION_ID"]);
	$arParams["CHECK_CREATOR"] = ($arParams["OBJECT"]->check_creator ? "Y" : "N");
	$ob->IsDir(array("section_id" => $arParams["SECTION_ID"])); 
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["REPLACE_SYMBOLS"] = ($arParams["REPLACE_SYMBOLS"] == "Y" ? "Y" : "N");
	$arParams["ACTION"] = strToUpper(!empty($arParams["ACTION"]) ? $arParams["ACTION"] : $_REQUEST["ACTION"]);
	$arParams["ACTION"] = ($ob->arParams["not_found"] ? "ADD" : $arParams["ACTION"]); 
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#", 
		"section_edit" => "PAGE_NAME=section_edit&SECTION_ID=#SECTION_ID#&ACTION=#ACTION#",
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
	$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") === false ? true : false);
	if (!$arParams["CONVERT"])
		$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#")); 
/***************** ADDITIONAL **************************************/
	$arParams["USE_WORKFLOW"] = ($ob->workflw ? "Y" : "N");
	$arParams["FORM_ID"] = "webdav_section_edit"; 
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

if ($arParams["PERMISSION"] < "W" || $arParams["CHECK_CREATOR"] == "Y"):
	ShowError(GetMessage("WD_ERROR_ACCESS_DENIED"));
	return 0;
endif;

/********************************************************************
				Default params
********************************************************************/
$aMsg = array();
$bVarsFromForm = false;
$arParams["SECTION_ID"] = $ob->arParams["item_id"]; 
$arResult["NAV_CHAIN"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), false);
$arResult["NAV_CHAIN_UTF8"] = $ob->GetNavChain(array("section_id" => $arParams["SECTION_ID"]), true);
if ($arParams["ACTION"] != "ADD")
{
	$arResult["SECTION"] = (is_array($ob->arParams["dir_array"]) ? $ob->arParams["dir_array"] : array());
	$arResult["SECTION"]["PATH"] = "/".implode("/", $arResult["NAV_CHAIN"]); 
}
else
{
	$arResult["SECTION"] = array("IBLOCK_SECTION_ID" => $arParams["SECTION_ID"]); 
}

$ob->_get_section_info_arr($arResult["SECTION"]);
__prepare_item_info($arResult['SECTION'], $arParams);
$arResult["ROOT_SECTION"] = $ob->arRootSection; 
$arResult["IBLOCK_SECTION"] = array();
$arResult["USER"] = array();
$arResult["USERS"] = array(
	$arResult["SECTION"]["~MODIFIED_BY"] => $arResult["SECTION"]["MODIFIED_BY"], 
	$arResult["SECTION"]["~CREATED_BY"] => $arResult["SECTION"]["CREATED_BY"], 
	$arResult["SECTION"]["~WF_LOCKED_BY"] => $arResult["SECTION"]["WF_LOCKED_BY"]);
$arResult["SECTION"]["MODIFIED_BY"] = $arResult["SECTION"]["~MODIFIED_BY"]; 
$arResult["SECTION"]["CREATED_BY"] = $arResult["SECTION"]["~CREATED_BY"]; 
$arResult["SECTION"]["WF_LOCKED_BY"] = $arResult["SECTION"]["~WF_LOCKED_BY"]; 
/********************************************************************
				/Default params
********************************************************************/

/********************************************************************
				Actions
********************************************************************/
if (strToUpper($_REQUEST["edit_section"]) == "Y")
{
	array_walk($_REQUEST, '__UnEscape');
	array_walk($_FILES, '__UnEscape');
	$ob->IsDir(array("section_id" => $_REQUEST["IBLOCK_SECTION_ID"])); 
	$_REQUEST["IBLOCK_SECTION_ID"] = ($ob->arParams["not_found"] ? "" : $_REQUEST["IBLOCK_SECTION_ID"]); 
	$_REQUEST["IBLOCK_SECTION_ID"] = (empty($_REQUEST["IBLOCK_SECTION_ID"]) && $ob->arRootSection ? $ob->arRootSection["ID"] : $_REQUEST["IBLOCK_SECTION_ID"]); 
	$path = ""; 
	if (!empty($_REQUEST["cancel"]))
	{
	}
	elseif (!check_bitrix_sessid())
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
	elseif ($arParams["ACTION"] == "UNDELETE")
	{
		$result = $ob->Undelete(array("section_id" => $arParams["SECTION_ID"], "dest_url" => $arResult["SECTION"]["UNDELETE"])); 
		if (intVal($result) != 204)
		{
			$arError[] = array(
				"id" => "recover_error",
				"text" => GetMessage("WD_ERROR_RECOVER"));
		}
		else
		{
			$options["section_id"] = $arParams["SECTION_ID"];
			$ob->IsDir($options);
			if ($ob->arParams["not_found"])
			{
				$arError[] = array(
					"id" => "recover_error",
					"text" => GetMessage("WD_ERROR_RECOVER"));
			}
			else
			{
				$url =  str_replace(array("///", "//"), "/", WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
					array("PATH" => $ob->_get_path($ob->arParams["parent_id"], false))), array('result' => "sec" . $arParams["SECTION_ID"]))); 
			}
			if (empty($arError))
				WDClearComponentCache(array(
					"webdav.element.edit", 
					"webdav.element.hist", 
					"webdav.element.upload", 
					"webdav.element.view", 
					"webdav.menu",
					"webdav.section.edit", 
					"webdav.section.list"));
		}
	}
	elseif ($arParams["ACTION"] == "DROP")
	{
		if($_REQUEST['get_count_elements'])
		{
			$count = 0;
			$count += CIBlockSection::GetCount(array('IBLOCK_ID' => $ob->IBLOCK_ID, 'LEFT_MARGIN' => $arResult["SECTION"]['LEFT_MARGIN'], 'RIGHT_MARGIN' => $arResult["SECTION"]['RIGHT_MARGIN'])) - 1;
			$count += CIBlockElement::GetList(array(), array('IBLOCK_ID' => $ob->IBLOCK_ID, 'INCLUDE_SUBSECTIONS' => 'Y', 'SECTION_ID' => $arParams["SECTION_ID"]), array());

			CWebDavTools::sendJsonResponse(array(
				'status' => 'success',
				'items' => $count,
			));
		}
		elseif($_REQUEST['portion_delete'])
		{
			//delete elements
			$rs = CIBlockElement::GetList(null,
				array('IBLOCK_ID' => $ob->IBLOCK_ID, 'INCLUDE_SUBSECTIONS' => 'Y', 'SECTION_ID' => $arParams["SECTION_ID"]),
				false,
				array('nTopCount' => 100),
				array('ID')
			);
			$maxExecTime = 10;
			$startTime = time();
			$deleteCounter = 0;
			$successDeleteAllElements = false;
			while ($file = $rs->Fetch())
			{
				$ob->DELETE(array("element_id" => $file['ID']));
				$deleteCounter++;

				if (time() - $startTime > $maxExecTime)
				{
					CWebDavTools::sendJsonResponse(array(
						'status' => 'success',
						'deleteItems' => $deleteCounter,
						'limitTime' => true,
						'finish' => false,
					));
				}
			}
			$successDeleteAllElements = true;

			$successDeleteAllSections = false;
			//if delete all elements, then delete sections
			if($successDeleteAllElements || $deleteCounter == 0)
			{
				$rs = CIBlockSection::GetList(array(),
					array('IBLOCK_ID' => $ob->IBLOCK_ID, 'SECTION_ID' => $arParams["SECTION_ID"]),
					false,
					array('ID')
				);

				$startTime = time();
				while($s = $rs->fetch())
				{
					$ob->DELETE(array("section_id" => $s['ID']));
					$deleteCounter++;

					if (time() - $startTime > $maxExecTime)
					{
						CWebDavTools::sendJsonResponse(array(
							'status' => 'success',
							'deleteItems' => $deleteCounter,
							'limitTime' => true,
							'finish' => false,
						));
					}
				}
				$successDeleteAllSections = true;
			}

			if($successDeleteAllSections || $deleteCounter == 0)
			{
				CWebDavTools::sendJsonResponse(array(
					'status' => 'success',
					'finish' => true,
				));
			}
			else
			{
				CWebDavTools::sendJsonResponse(array(
					'status' => 'success',
					'deleteItems' => $deleteCounter,
					'finish' => false,
				));
			}

		}

		$deleteOptions = array("section_id" => $arParams["SECTION_ID"]);
		if(!empty($_GET['delete_without_trash']))
		{
			$deleteOptions["force"] = true;
		}
		$result = $ob->DELETE($deleteOptions);

		if (intVal($result) != 204)
		{ 
			$aMsg[] = array(
				"id" => "not_delete",
				"text" => GetMessage("WD_ERROR_DELETE"));
		}
		else
		{
			WDClearComponentCache(array(
				"webdav.element.edit", 
				"webdav.element.hist", 
				"webdav.element.upload", 
				"webdav.element.view", 
				"webdav.menu",
				"webdav.section.edit", 
				"webdav.section.list"));

			$arNavChain = $arResult["NAV_CHAIN"]; 
			array_pop($arNavChain);
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)));
			
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
				$arNavChain = ($arParams["CONVERT"] ? $arResult["NAV_CHAIN_UTF8"] : $arResult["NAV_CHAIN"]); 
				array_pop($arNavChain);
				$url = WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain))), array("result" => 
					(($arParams["SECTION_ID"] == $ob->GetMetaID('TRASH')) ? "empty_trash" : "section_deleted")));
				LocalRedirect($url);
			}
			else 
			{
				$arNavChain = $arResult["NAV_CHAIN"]; 
				array_pop($arNavChain);
				$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)));
				$APPLICATION->RestartBuffer();
				?><?=CUtil::PhpToJSObject(array("result" => (($arParams["SECTION_ID"] == $ob->GetMetaID('TRASH')) ? "empty_trash" : "section_deleted"), "url" => $url));?><?
				die();
			}
		}
	}
	elseif (!isset($_REQUEST["NAME"]) || strlen(trim($_REQUEST["NAME"]))<1)
	{
		$aMsg[] = array(
			"id" => "empty_section_name",
			"text" => GetMessage("WD_ERROR_EMPTY_SECTION_NAME"));
	}
	elseif ($arParams["REPLACE_SYMBOLS"] == "N" && !$ob->CheckName($_REQUEST["NAME"]))
	{
		$aMsg[] = array(
			"id" => "bad_section_name",
			"text" => GetMessage("WD_ERROR_BAD_SECTION_NAME"));
	}
	elseif ($arParams["ACTION"] == "ADD")
	{
		$_REQUEST["NAME"] = $ob->CorrectName($_REQUEST["NAME"]);
		$path = $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false); 
		$options = array("path" => str_replace("//", "/", $path."/".$_REQUEST["NAME"])); 
		$ob->MKCOL($options); 
		$path = $options["path"]; 
	}
	else
	{
		$options = array(
			"path" => $ob->_get_path($arParams["SECTION_ID"], false), 
			"dest_url" => str_replace(array("//", "/"), "/", $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false)."/".$_REQUEST["NAME"]."/")); 
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
		$arNavChain = ($arParams["CONVERT"] && $_REQUEST["AJAX_CALL"] != "Y" ? $arResult["NAV_CHAIN_UTF8"] : $arResult["NAV_CHAIN"]); 
		
		if (!empty($_REQUEST["apply"]))
		{
			if ($arParams["CONVERT"] && $_REQUEST["AJAX_CALL"] != "Y" && SITE_CHARSET != "UTF-8")
				$path = $APPLICATION->ConvertCharset($path, SITE_CHARSET, "UTF-8"); 
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTION_EDIT_URL"], 
				array("PATH" => $path, "SECTION_ID" => $arParams["SECTION_ID"], "ACTION" => "EDIT"));
		}
		elseif ($arParams["ACTION"] == "ADD")
		{
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
				array("PATH" => implode("/", $arNavChain)));
			if (isset($ob->arParams["changed_element_id"]))
				$url = WDAddPageParams($url, array('result' => "sec".(int)$ob->arParams["changed_element_id"]));
		}
		elseif (!isset($url))
		{
			array_pop($arNavChain);
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
				array("PATH" => implode("/", $arNavChain)));
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
		if ($oError):
			$arResult["ERROR_MESSAGE"] = $oError->GetString();
		endif;
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

$_REQUEST["NAME"] = htmlspecialcharsbx($_REQUEST["NAME"]);
$_REQUEST["IBLOCK_SECTION_ID"] = htmlspecialcharsbx($_REQUEST["IBLOCK_SECTION_ID"]);

foreach ($arResult["SECTION"] as $key => $val) 
{
	if (substr($key, 0, 1) == "~")
		continue; 
	elseif (!is_set($arResult["SECTION"], "~".$key))
		$arResult["SECTION"]["~".$key] = $val;

	$arResult["SECTION"][$key] = htmlspecialcharsEx($val);
}

$arResult["SECTION_LIST"] = $ob->GetSectionsTree(array("path" => "/")); 
$arNavChain = ($arParams["CONVERT"] && $_REQUEST["AJAX_CALL"] != "Y" ? $arResult["NAV_CHAIN_UTF8"] : $arResult["NAV_CHAIN"]); 

if (intVal($arResult["SECTION"]["CREATED_BY"]) > 0)
{
	$db_res = CUser::GetByID($arResult["SECTION"]["CREATED_BY"]);
	if ($db_res && $res = $db_res->GetNext())
	{
		$res["URL"] = CComponentEngine::MakePathFromTemplate($arParams["USER_VIEW_URL"], 
					array("USER_ID" => $res["ID"]));
		$arResult["USER"]["USER_".$arResult["SECTION"]["CREATED_BY"]] = $res;
	}
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
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(($arParams["ACTION"] == "ADD" ? GetMessage("WD_NEW") : htmlspecialcharsEx($arResult["SECTION"]["~NAME"])));
}
if ($arParams["SET_NAV_CHAIN"] == "Y")
{
	$res = array(); 
	$sTitle = ($arParams["ACTION"] == "ADD" ? GetMessage("WD_NEW") : array_pop($arResult["NAV_CHAIN"])); 
	foreach ($arResult["NAV_CHAIN"] as $name)
	{
		$res[] = $ob->_uencode($name); 
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($name), CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $res))));
	}
	
	$GLOBALS["APPLICATION"]->AddChainItem($sTitle);
}

if ($arParams["DISPLAY_PANEL"] == "Y" && $USER->IsAuthorized() && CModule::IncludeModule("iblock"))
	CIBlock::ShowPanel($arParams["IBLOCK_ID"], 0, $arParams["SECTION_ID"], $arParams["IBLOCK_TYPE"], false, $this->GetName());
/********************************************************************
				/Standart operations
********************************************************************/
return $arResult['SECTION'];
?>
