<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if ($_REQUEST["edit"] != "Y" && !is_set($_POST, "action_button_".$arParams["GRID_ID"]) || $ob->permission < "W"):
	return true;
endif;

$this->IncludeComponentLang("action.php");
$APPLICATION->ResetException();

$_REQUEST["IBLOCK_SECTION_ID"] = (empty($_REQUEST["IBLOCK_SECTION_ID"]) ? "/" : $_REQUEST["IBLOCK_SECTION_ID"]); 
$_REQUEST["ID"] = (is_array($_REQUEST["ID"]) ? $_REQUEST["ID"] : array());

$_REQUEST["ACTION"] = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : (!empty($_POST["action_button_".$arParams["GRID_ID"]]) ? $_POST["action_button_".$arParams["GRID_ID"]] : "move"); 
$_REQUEST["ACTION"] = strtoupper($_REQUEST["ACTION"]); 
/************** Folders and files **********************************/
$arData = array("S" => array(), "E" => array()); 
if ($_POST["action_all_rows_".$arParams["GRID_ID"]] == "Y")
{
	$res = $ob->PROPFIND($options = array("path" => $ob->_path, "depth" => 1), $files, array("COLUMNS" => array("ID", "NAME"), "return" => "nav_result")); 
	$db_res = $res["NAV_RESULT"];
	if ($db_res && $res = $db_res->Fetch())
	{
		do
		{
			$arData[$res["TYPE"]][] = $res["ID"]; 
		} while ($res = $db_res->Fetch()); 
	}
}
elseif (!empty($_POST) && !empty($_REQUEST["FIELDS"]))
{
	foreach ($_REQUEST["FIELDS"] as $key => $value)
	{
		if (substr($key, 0, 1) == "S")
			$arData["S"][] = substr($key, 1); 
		else
			$arData["E"][] = substr($key, 1); 
	}
}
else
{
	foreach ($_REQUEST["ID"] as $key)
	{
		$arData[(substr($key, 0, 1) == "S" ? "S" : "E")][] = substr($key, 1); 
	}
}
/************** Action *********************************************/
$ACTION = strtoupper($_POST["action_button_".$arParams["GRID_ID"]]); 
/************** Main errors ****************************************/
if (!check_bitrix_sessid())
{
	$aMsg[] = array(
		"id" => "SESSID", 
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
if ($arParams["PERMISSION"] < "W")
{
	$aMsg[] = array(
		"id" => "PERMISSION", 
		"text" => GetMessage("WD_ACCESS_DENIED"));
}
if (empty($ACTION))
{
	$aMsg[] = array(
		"id" => "ACTION", 
		"text" => GetMessage("WD_ERROR_EMPTY_ACTION"));
}
elseif (!in_array($ACTION, array("EDIT", "MOVE", "DELETE", "UNDELETE")))
{
	$aMsg[] = array(
		"id" => "ACTION", 
		"text" => GetMessage("WD_ERROR_BAD_ACTION"));
}
if (empty($arData["S"]) && empty($arData["E"]))
{
	$aMsg[] = array(
		"id" => "DATA", 
		"text" => GetMessage("WD_ERROR_EMPTY_DATA"));
}
if ($ACTION == "MOVE" && $_REQUEST["IBLOCK_SECTION_ID"] === false)
{
	$aMsg[] = array(
		"id" => "TARGET_SECTION", 
		"text" => GetMessage("WD_ERROR_EMPTY_TARGET_SECTION"));
}
/************** Main errors/****************************************/

/************** Data errors ****************************************/
if ($ACTION == "MOVE" && $_REQUEST["IBLOCK_SECTION_ID"] == $arParams["SECTION_ID"])
{
	"It is not need any actions"; 
}
elseif (!empty($aMsg))
{
}
elseif ($ACTION == "MOVE")
{
	
	@set_time_limit(1000);
	$result = $ob->PROPFIND($options = array("path" => $ob->_path, "depth" => 1), $files, array("return" => "array")); 
	if (!empty($result["RESULT"]))
	{
		foreach ($result["RESULT"] as $key => $res)
		{
			if (!in_array($res["ID"], $arData[$res["TYPE"]]))
				continue; 

			$options = array(
				"dest_url" => str_replace("//", "/", $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false) . "/" . $res["NAME"]), 
				"overwrite" => true, 
				"path" => $res["ID"]); 
			
			$APPLICATION->ResetException();
			

			$result = $ob->MOVE($options); 
			
			$oError = $APPLICATION->GetException();
			if ($oError || intVal($result) >= 300)
				$aMsg[] = array(
					"id" => "ELEMENTS[".$res["TYPE"]."][".$res["ID"]."]",
					"text" => ($oError ? $oError->GetString() : $result));
		}
	}
}
elseif ($_REQUEST["ACTION"] == "EDIT") // rename
{
	$APPLICATION->ResetException();
	$options = array("path" => $ob->_path, "depth" => 1); 
	$result = $ob->PROPFIND($options, $files, array("COLUMNS" => array("ID", "NAME"), "return" => "array")); 
	if (!empty($result["RESULT"]))
	{
		foreach ($result["RESULT"] as $key => $res)
		{
			$options = false; 
			$path = $ob->_udecode($ob->_path);
			if (($res["TYPE"] == "S" || $res["TYPE"] == "FOLDER") && in_array($res["ID"], $arData["S"]))
			{
				$destName = $ob->CorrectName($_REQUEST["FIELDS"]["S".$res["ID"]]["NAME"]);
				$options = array(
					"section_id" => $res["ID"], 
					"dest_url" => str_replace("//", "/", $path . "/" . $destName), 
					"overwrite" => true); 
				$redirectTo = urldecode(str_replace("//", "/", $path /*. "#sec" . $options["section_id"]*/));
			}
			elseif (($res["TYPE"] == "E" || $res["TYPE"] == "FILE") && in_array($res["ID"], $arData["E"]))
			{
				$destName = $ob->CorrectName($_REQUEST["FIELDS"]["E".$res["ID"]]["NAME"]);
				$options = array(
					"element_id" => $res["ID"], 
					"dest_url" => str_replace("//", "/", $path . "/" . $destName), 
					"overwrite" => true); 
				$redirectTo = urldecode(str_replace("//", "/", $path /* . "#doc" . $options["element_id"]*/));
			}
			if ($options)
			{
				if (strlen($destName) == 0)
				{
					$aMsg[] = array(
						"id" => "bad_move_message", 
						"text" => GetMessage("WD_ERROR_EMPTY_NAME"));
				}
				else
				{
					$res = $ob->MOVE($options); 
					$oError = $APPLICATION->GetException();
					if (!$oError && intVal($res) >= 300): 
						$aMsg[] = array(
							"id" => "bad_move_message", 
							"text" => $res);
					elseif ($oError):
						$aMsg[] = array(
							"id" => "bad_move_message", 
							"text" => $oError->GetString());
					endif;
				}
			}
		}
	}
}
elseif ($ACTION == "DELETE") 
{
	@set_time_limit(1000);
	foreach ($arData["S"] as $section_id):
		$result = $ob->DELETE(array("path" => str_replace(array("///", "//"), "/", "/".$section_id))); 
		if (intVal($result) != 204)
			$aMsg[] = array(
				"id" => "ELEMENTS[S][".$section_id."]",
				"text" => GetMessage("WD_ERROR_DELETE")); 
	endforeach;
	
	foreach ($arData["E"] as $element_id):
		$result = $ob->DELETE(array("path" => str_replace(array("///", "//"), "/", "/".$element_id))); 
		if (intVal($result) != 204) 
			$aMsg[] = array(
				"id" => "ELEMENTS[E][".$element_id."]",
				"text" => GetMessage("WD_ERROR_DELETE")." ".$result); 
	endforeach;
}
elseif ($_REQUEST["ACTION"] == "UNDELETE") 
{
	@set_time_limit(0);
	foreach ($arData["S"] as $section_id):
		$result = $ob->Undelete(array("path" => str_replace(array("///", "//"), "/", "/".$section_id))); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => "ELEMENTS[S][".$section_id."]",
				"text" => GetMessage("WD_ERROR_UNDELETE")); 
		endif;
	endforeach;
	
	foreach ($arData["E"] as $element_id):
		$result = $ob->Undelete(array("path" => str_replace(array("///", "//"), "/", "/".$element_id))); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => "ELEMENTS[E][".$element_id."]",
				"text" => GetMessage("WD_ERROR_UNDELETE")); 
		endif;
	endforeach;
}

if (!empty($aMsg)):
	$e = new CAdminException($aMsg);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	return false;
endif;

$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") === false ? true : false);
if (!$arParams["CONVERT"])
	$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#")); 
$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
	array("PATH" => implode("/", ($arResult["NAV_CHAIN"]))));
if ($ACTION == "DELETE")
	$url = WDAddPageParams($url, array("result"=>"deleted"));
elseif ($ACTION == "UNDELETE")
	$url = WDAddPageParams($url, array("result"=>"all_restored"));
LocalRedirect($url);
?>
