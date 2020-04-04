<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	if (!is_object($arParams["OBJECT"]))
		$arParams["OBJECT"] = new CWebDavFile($arParams, $arParams['BASE_URL']);
	$ob = $arParams["OBJECT"]; 
	$ob->IsDir(); 
	$arParams["ELEMENT_ID"] = $arParams["SECTION_ID"] = $ob->arParams["item_id"];
	$arParams["PERMISSION"] = $ob->permission;
	$arParams["ACTION"] = strToUpper(!empty($arParams["ACTION"]) ? $arParams["ACTION"] : $_REQUEST["ACTION"]);
	$arParams["ACTION"] = ($ob->arParams["not_found"] ? "ADD" : $arParams["ACTION"]); 
/***************** URL *********************************************/
	$URL_NAME_DEFAULT = array(
		"sections" => "PAGE_NAME=sections&PATH=#PATH#", 
		"element_edit" => "PAGE_NAME=element_edit&ELEMENT_ID=#ELEMENT_ID#&ACTION=#ACTION#");
	foreach ($URL_NAME_DEFAULT as $URL => $URL_VALUE)
	{
		if (empty($arParams[strToUpper($URL)."_URL"]))
			$arParams[strToUpper($URL)."_URL"] = $GLOBALS["APPLICATION"]->GetCurPageParam($URL_VALUE, array("PAGE_NAME", "PATH", 
				"SECTION_ID", "ELEMENT_ID", "ACTION", "AJAX_CALL", "USER_ID", "sessid", "save", "login", "edit", "action", "edit_section", "result"));
			//$arParams[strToUpper($URL)."_URL"] = $APPLICATION->GetCurPage().($URL == "index" ? "" : "?");
		$arParams["~".strToUpper($URL)."_URL"] = $arParams[strToUpper($URL)."_URL"];
		$arParams[strToUpper($URL)."_URL"] = htmlspecialcharsbx($arParams["~".strToUpper($URL)."_URL"]);
	}
/***************** ADDITIONAL **************************************/
	$arParams["FORM_ID"] = "webdav_file_edit"; 
//***************** STANDART ****************************************/
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] != "N" ? "Y" : "N"); //Turn on by default
	$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y"); //Turn on by default
/********************************************************************
				/Input params
********************************************************************/
$sError = '';
if ($ob->arParams["not_found"])
{
	$ob->IsDir(array("element_id" => $_REQUEST["ID"])); 
}

if ($ob->arParams["not_found"])
{
	$sError = GetMessage("WD_ERROR_ELEMENT_NOT_FOUND");
	//return false; 
}

if ($ob->permission < "R")
{
	$sError = GetMessage("WD_ACCESS_DENIED");
	//return false; 
}

$arResult["NAV_CHAIN"] = $ob->GetNavChain(); 
$arResult["ELEMENT"] = $ob->arParams["element_array"];
$arResult["ELEMENT"]["FILE_EXTENTION"] = $arResult["ELEMENT"]["EXTENTION"];
$arResult["ELEMENT"]["PROPERTIES"] = $ob->_get_props($arParams["SECTION_ID"]);
$arElement = $arResult["ELEMENT"]; 

$arResult["WRITEABLE"] = (isset($arElement["PROPERTIES"]["UNDELETEBX:"]) ? "N" : "Y");
if ($arParams["PERMISSION"] > "W")
	$arResult["WRITEABLE"] = "Y"; // admin can delete from trash
if ( !$ob->CheckRights('PUT', false, $arResult["ELEMENT"]["ID"] ))
{
	if ($arParams["PERMISSION"] >= "R")
	{
		$arResult["WRITEABLE"] = "N";
	}
	else
	{ 
		$sError = GetMessage("WD_ACCESS_DENIED");
		//return 0;
	}
}

if (strlen($sError) > 0)
{
	ShowError($sError);
	unset($arResult["ELEMENT"]);
}
else
{
	if (isset($_REQUEST["result"]) && $_REQUEST["result"]=="uploaded")
	{
		$arResult["NOTIFY_MESSAGE"] = GetMessage("WD_UPLOAD_DONE");
	}
	/********************************************************************
					Actions
	********************************************************************/
	if (empty($_REQUEST["cancel"]) && check_bitrix_sessid() && $arResult["WRITEABLE"] === "Y")
	{
		$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") === false ? true : false);
		if (!$arParams["CONVERT"])
			$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#")); 
		$arNavChain = $ob->GetNavChain(array("path" => $ob->_path), $arParams["CONVERT"]); 
		
		if ($arParams["ACTION"] == "DELETE")
		{
			$ob->DELETE(array("path" => $ob->_path)); 
		}
		elseif ($arParams["ACTION"] == "UNDELETE")
		{
			$ob->IsDir(array("path" => $arParams["SECTION_ID"]));
			if ($ob->arParams["not_found"] === false)
			{
				if (isset($arElement["PROPERTIES"]["UNDELETEBX:"]))
				{
					$destPath = $arElement["PROPERTIES"]["UNDELETEBX:"]["value"];
					$result = $ob->Undelete(array("path" => $arParams["SECTION_ID"], "dest_url" => $destPath)); 
					if (intVal($result) != 204)
					{
						$arError[] = array(
							"id" => "recover_error",
							"text" => GetMessage("WD_ERROR_RECOVER"));
					}
					else
					{
						$options["element_id"] = $arParams["ELEMENT_ID"];
						$ob->IsDir($options);
						if ($ob->arParams["not_found"])
						{
							$arError[] = array(
								"id" => "recover_error",
								"text" => GetMessage("WD_ERROR_RECOVER"));
						}
						else
						{
							$url = str_replace(array("///", "//"), "/", CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
								array("PATH" => $ob->_udecode($ob->_get_path($ob->arParams["parent_id"]))))) . "#doc" . $arParams["ELEMENT_ID"]; 
						}
					}
				}
			}
		}
		else
		{
			$options = array("path" => $ob->arParams["item_id"], "dest_url" => $ob->_get_path($ob->arParams["parent_id"], false).$_REQUEST["NAME"]);  
			$ob->MOVE($options); 
			if (!empty($_FILES["FILE"]) && $_FILES["FILE"]["error"] <= 0)
			{
				$res = array("path" => $options["dest_url"], "fopen" => "N"); 
				if ($ob->PUT($res) === true)
				{
					CopyDirFiles($_FILES["FILE"]["tmp_name"], $res["fspath"], true); 
					$ob->put_commit($res); 
				}
			}
		}
		
		$oError = $APPLICATION->GetException();
		if ($oError):
			ShowError($oError->GetString());
		else: 
			if (!empty($_REQUEST["apply"]))
			{
				$options["dest_url"] = $ob->_uencode($options["dest_url"], array("utf8" => "Y", "convert" => "full"));
				$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
					array("PATH" => $options["dest_url"], "ACTION" => "EDIT"));
			}
			else
			{
				array_pop($arNavChain);
				$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)));
				if ($arParams["ACTION"] == "DELETE")
					$url = WDAddPageParams($url, array("result"=>"deleted"));
			}
			LocalRedirect($url);
		endif;
	}
	/********************************************************************
					/ Actions
	********************************************************************/

	/********************************************************************
					Data
	********************************************************************/
	$oFile = $ob->GetIo()->GetFile($ob->GetIo()->CombinePath($ob->real_path_full, $arElement["ID"]));
	$arElement["FILE_SIZE"] = $oFile->GetFileSize(); 
	__parse_file_size($arElement, $arResult["ELEMENT"]); 
	$arResult["ELEMENT"]["BASE_NAME"] = str_replace($arResult["ELEMENT"]["EXTENTION"], "", $arResult["ELEMENT"]["NAME"]); 
	$arResult["ELEMENT"]["~TIMESTAMP_X"] = $oFile->GetModificationTime() + CTimeZone::GetOffset(); 
	$arResult["ELEMENT"]["TIMESTAMP_X"] = ConvertTimeStamp($arResult["ELEMENT"]["~TIMESTAMP_X"], "FULL"); 
	$arResult["ELEMENT"]["URL"] = array(
		"THIS" => CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))), 
		"~THIS" => CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]))),
		"UPLOAD" => CComponentEngine::MakePathFromTemplate($arParams["UPLOAD_URL"], array("PATH" => implode("/", array_map('urlencode', $arResult["NAV_CHAIN"])))),
		"DELETE" => WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["ELEMENT_EDIT_URL"], array("PATH" => implode("/", $arResult["NAV_CHAIN"]), "ACTION" => "DELETE")), array("edit" => "y", "sessid" => bitrix_sessid())), 
	);
	/********************************************************************
					/ Data
	********************************************************************/
}
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
	$arNavChain = array(); 
	foreach ($arResult['NAV_CHAIN'] as $res)
	{
		$arNavChain[] = $res;
		$url = CComponentEngine::MakePathFromTemplate(
			$arParams["~SECTIONS_URL"], array("PATH" => implode("/", $arNavChain)));
		$GLOBALS["APPLICATION"]->AddChainItem(htmlspecialcharsEx($res), $url);
	}
}
/********************************************************************
				/Standart operations
********************************************************************/
?>
