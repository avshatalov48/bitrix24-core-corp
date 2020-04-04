<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if ($_REQUEST["edit"] != "Y" && !is_set($_POST, "action_button_".$arParams["GRID_ID"])):
	return true;
endif;

$this->IncludeComponentLang("action.php");

$_REQUEST["IBLOCK_SECTION_ID"] = (is_set($_REQUEST, "IBLOCK_SECTION_ID") ? intVal($_REQUEST["IBLOCK_SECTION_ID"]) : false);
$_REQUEST["IBLOCK_SECTION_ID"] = ($_REQUEST["IBLOCK_SECTION_ID"] <= 0 && $ob->arRootSection ? $ob->arRootSection["ID"] : $_REQUEST["IBLOCK_SECTION_ID"]);

$_REQUEST["ELEMENTS"]["S"] = !is_array($_REQUEST["ELEMENTS"]["S"]) ? array() : $_REQUEST["ELEMENTS"]["S"];
$_REQUEST["ELEMENTS"]["E"] = !is_array($_REQUEST["ELEMENTS"]["E"]) ? array() : $_REQUEST["ELEMENTS"]["E"];

if (is_set($_POST, "action_button_".$arParams["GRID_ID"]))
{
	if ($_POST["action_all_rows_".$arParams["GRID_ID"]] == "Y" && $ob->permission >= "W")
	{
		$options = array("path" => $ob->_path, "depth" => 1); 
		$res = $ob->PROPFIND($options, $files, array("COLUMNS" => array("ID", "NAME"), "return" => "nav_result")); 
		$db_res = isset($res["NAV_RESULT"]) && is_object($res["NAV_RESULT"])? $res["NAV_RESULT"]: false;
		if ($db_res && $res = $db_res->Fetch())
		{
			do
			{
				if ($res["TYPE"] == "S")
					$_REQUEST["ELEMENTS"]["S"][] = $res["ID"]; 
				else
					$_REQUEST["ELEMENTS"]["E"][] = $res["ID"]; 
			} while ($res = $db_res->Fetch()); 
		}
	}
	elseif (!empty($_POST) && !empty($_REQUEST["ID"]))
	{
        if (!is_array($_REQUEST["ID"]))
        {
            $tmpID = $_REQUEST["ID"];
            $_REQUEST["ID"] = array($tmpID);
        }
        foreach ($_REQUEST["ID"] as $key)
        {
            if (substr($key, 0, 1) == "S")
                $_REQUEST["ELEMENTS"]["S"][] = substr($key, 1); 
            else
                $_REQUEST["ELEMENTS"]["E"][] = substr($key, 1); 
        }
    }
	elseif (!empty($_POST) && !empty($_REQUEST["FIELDS"]))
	{
        foreach ($_REQUEST["FIELDS"] as $key => $value)
        {
            if (substr($key, 0, 1) == "S")
                $_REQUEST["ELEMENTS"]["S"][] = substr($key, 1); 
            else
                $_REQUEST["ELEMENTS"]["E"][] = substr($key, 1); 
        }
	}
	$_REQUEST["ACTION"] = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : (!empty($_POST["action_button_".$arParams["GRID_ID"]]) ? $_POST["action_button_".$arParams["GRID_ID"]] : "move"); 
}
$_REQUEST["ACTION"] = strtoupper($_REQUEST["ACTION"]); 

$aMsg = array();
$redirectTo = "";
/************** Main errors ****************************************/

if (!check_bitrix_sessid())
{
	$aMsg[] = array(
		"id" => "SESSID", 
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
if ($arParams['PERMISSION'] < "U" || ($_REQUEST["ACTION"] == "DELETE" && $arParams["PERMISSION"] < "W"))
{
	$aMsg[] = array(
		"id" => "PERMISSION", 
		"text" => GetMessage("WD_ACCESS_DENIED"));
}
if (empty($_REQUEST["ACTION"]))
{
	$aMsg[] = array(
		"id" => "ACTION", 
		"text" => GetMessage("WD_ERROR_EMPTY_ACTION"));
}
if (!in_array($_REQUEST["ACTION"], array("COPY", "MOVE", "LOCK", "UNLOCK", "DELETE", "EDIT", "UNDELETE")))
{
	$aMsg[] = array(
		"id" => "ACTION", 
		"text" => GetMessage("WD_ERROR_BAD_ACTION"));
}
if (empty($_REQUEST["ELEMENTS"]) || (empty($_REQUEST["ELEMENTS"]["S"]) && empty($_REQUEST["ELEMENTS"]["E"])) || 
	(in_array($_REQUEST["ACTION"], array("LOCK", "UNLOCK")) && empty($_REQUEST["ELEMENTS"]["E"])))
{
	$aMsg[] = array(
		"id" => "DATA", 
		"text" => GetMessage("WD_ERROR_EMPTY_DATA"));
}
if ($_REQUEST["ACTION"] == "MOVE" && $_REQUEST["IBLOCK_SECTION_ID"] === false)
{
	$aMsg[] = array(
		"id" => "TARGET_SECTION", 
		"text" => GetMessage("WD_ERROR_EMPTY_TARGET_SECTION"));
}
/************** Main errors ****************************************/
/************** Data errors ****************************************/
$redirectTo = "";
$redirectHilight = "";
if ($_REQUEST["ACTION"] == "MOVE" && $_REQUEST["IBLOCK_SECTION_ID"] == $arParams["SECTION_ID"])
{
	"No need actions"; 
}
elseif (!empty($aMsg))
{
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
			if (($res["TYPE"] == "S" || $res["TYPE"] == "FOLDER") && in_array($res["ID"], $_REQUEST["ELEMENTS"]["S"]))
			{
                $destName = $ob->CorrectName($_REQUEST["FIELDS"]["S".$res["ID"]]["NAME"]);
                if (strlen($destName) < 1)
                {
					$aMsg[] = array(
						"id" => "bad_move_message", 
						"text" => GetMessage("WD_ERROR_EMPTY_NAME"));
                    continue;
                }
				$options = array(
					"section_id" => $res["ID"], 
					"dest_url" => str_replace("//", "/", $ob->_udecode($ob->_path) . "/" . $destName), 
					"overwrite" => true); 
                $redirectTo = $ob->_path;
                $redirectHilight = "sec" . $options["section_id"];
				$res = $ob->MOVE($options); 
			}
			elseif (($res["TYPE"] == "E" || $res["TYPE"] == "FILE") && in_array($res["ID"], $_REQUEST["ELEMENTS"]["E"]))
			{
                $destName = $ob->CorrectName($_REQUEST["FIELDS"]["E".$res["ID"]]["NAME"]);
                if (strlen($destName) < 1)
                {
					$aMsg[] = array(
						"id" => "bad_move_message", 
						"text" => GetMessage("WD_ERROR_EMPTY_NAME"));
                    continue;
                }
				$options = array(
					"element_id" => $res["ID"], 
                    "dest_url" => str_replace("//", "/", $ob->_udecode($ob->_path) . "/" . $destName), 
                    "overwrite" => true); 
                $redirectTo = $ob->_path;
                $redirectHilight = "doc" . $options["element_id"];
				$res = $ob->MOVE($options); 
			}
			if ($options)
			{
				$oError = $APPLICATION->GetException();
				if (!$oError && intVal($res) >= 300)
				{
					$aMsg[] = array(
						"id" => "bad_move_message",
						"text" => $res
					);
				}
				elseif ($oError && $oError->GetID() != 'SAME_FOLDER_IS_EXISTS')
				{
					$aMsg[] = array(
						"id" => "bad_move_message",
						"text" => $oError->GetString()
					);
				}
			}
		}
	}
}
elseif (in_array($_REQUEST["ACTION"], array("MOVE", "COPY")))
{
    $APPLICATION->ResetException();
    $overwrite = isset($_REQUEST["overwrite"]);
    $overwrite = ($overwrite) ? (intval($_REQUEST["overwrite"]) === 1) : false;
    foreach ($_REQUEST["ELEMENTS"]["S"] as $sectionID) 
    {
        $ob->IsDir(array("section_id" => $sectionID));
        if (isset($ob->arParams["dir_array"]))
        {
			if (!isset($_REQUEST['fake']))
			{
				$tmpName = $ob->arParams["dir_array"]["NAME"];
				$options = array(
					"section_id" => $sectionID,
					"dest_url" => str_replace("//", "/", $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false) . "/" . $tmpName),
					"overwrite" => $overwrite);
				if ($_REQUEST["ACTION"] == "MOVE")
					$res = $ob->MOVE($options);
				elseif ($_REQUEST["ACTION"] == "COPY")
					$res = $ob->COPY($options);

				$oError = $APPLICATION->GetException();
				if (!$oError && intVal($res) >= 300)
				{
					$aMsg[] = array(
						"id" => "bad_move_message",
						"text" => $res);
				}
				elseif ($oError)
				{
					$aMsg[] = array(
						"id" => "bad_move_message",
						"text" => $oError->GetString());
				}
				else
				{
					if (isset($ob->arParams["changed_section_id"]))
					{
						$secID = $ob->arParams["changed_section_id"];
						$redirectTo = implode("/", array_slice(explode("/", $ob->_get_path($secID)), 0, -1));
						$redirectHilight = "sec".$secID;
					} else {
						$redirectTo = $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"]);
						$redirectHilight = "sec".$options["section_id"];
					}
				}
			}
			else // fake request
			{
				$arResult["DONE"] = true;
			}
        }
    }

    foreach ($_REQUEST["ELEMENTS"]["E"] as $elementID) 
    {
        $ob->IsDir(array("element_id" => $elementID));
        $tmpName = $ob->arParams["element_name"];
        $options = array(
            "element_id" => $elementID, 
            "dest_url" => str_replace("//", "/", $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false) . "/" . $tmpName), 
            "overwrite" => $overwrite); 

		if (!isset($_REQUEST['fake']))
		{
			if ($_REQUEST["ACTION"] == "MOVE")
				$res = $ob->MOVE($options); 
			elseif ($_REQUEST["ACTION"] == "COPY")
				$res = $ob->COPY($options); 

			$oError = $APPLICATION->GetException();
			if (!$oError && intVal($res) >= 300)
			{
				$aMsg[] = array(
					"id" => "bad_move_message", 
					"text" => $res);
			}
			elseif ($oError)
			{
				$aMsg[] = array(
					"id" => "bad_move_message", 
					"text" => $oError->GetString());
			}
			else
			{
				if (isset($ob->arParams["changed_element_id"]))
				{
					$elmID = $ob->arParams["changed_element_id"];
					$ob->IsDir(array("element_id" => $elmID));
					$secID = $ob->arParams["parent_id"];
					$redirectTo = $ob->_get_path($secID, false);
					$redirectHilight = "doc".$elmID;
				} else {
					$redirectTo = $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"]);
					$redirectHilight = "doc".$options["element_id"];
				}
			}
		}
		else // fake request
		{
			$arResult["DONE"] = true;
		}

    }
}
elseif ($_REQUEST["ACTION"] == "LOCK" || $_REQUEST["ACTION"] == "UNLOCK")
{
	foreach ($_REQUEST["ELEMENTS"]["E"] as $element_id):
		$options = array("element_id" => $element_id); 
		call_user_func(array($ob, $_REQUEST["ACTION"]), $options); 
	endforeach;
}
elseif ($_REQUEST["ACTION"] == "DELETE") 
{
	$ob->GetMetaID('TRASH');
	@set_time_limit(0);
	foreach ($_REQUEST["ELEMENTS"]["S"] as $section_id):
		$result = $ob->DELETE(array("section_id" => $section_id)); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => "ELEMENTS[S][".$section_id."]",
				"text" => GetMessage("WD_ERROR_DELETE")); 
		endif;
	endforeach;
	
	foreach ($_REQUEST["ELEMENTS"]["E"] as $element_id):
		$result = $ob->DELETE(array("element_id" => $element_id)); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => "ELEMENTS[E][".$element_id."]",
				"text" => GetMessage("WD_ERROR_DELETE")." ".$result); 
		endif;
	endforeach;
}
elseif ($_REQUEST["ACTION"] == "UNDELETE") 
{
	@set_time_limit(0);
	foreach ($_REQUEST["ELEMENTS"]["S"] as $section_id):
		$result = $ob->Undelete(array("section_id" => $section_id)); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => "ELEMENTS[S][".$section_id."]",
				"text" => GetMessage("WD_ERROR_UNDELETE")); 
		endif;
	endforeach;
	
	foreach ($_REQUEST["ELEMENTS"]["E"] as $element_id):
		$result = $ob->Undelete(array("element_id" => $element_id)); 
		if (intVal($result) != 204): 
			$aMsg[] = array(
				"id" => "ELEMENTS[E][".$element_id."]",
				"text" => GetMessage("WD_ERROR_UNDELETE")); 
		endif;
	endforeach;

	if (empty($aMsg))
	{
		$redirectTo = $ob->_get_path($ob->GetMetaID("TRASH"));
		$redirectHilight = 'all_restored';
	}
}
WDClearComponentCache(array(
	"webdav.element.edit", 
	"webdav.element.hist", 
	"webdav.element.upload", 
	"webdav.element.view", 
	"webdav.menu",
	"webdav.section.edit", 
	"webdav.section.list"));

if (!empty($aMsg)):
	$e = new CAdminException($aMsg);
	$GLOBALS["APPLICATION"]->ThrowException($e);
	return false;
else:
	$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") === false ? true : false);
	if (!$arParams["CONVERT"])
		$arParams["CONVERT"] = (strPos($arParams["~SECTIONS_URL"], "?") > strPos($arParams["~SECTIONS_URL"], "#PATH#")); 

    if (empty($redirectTo))
    {
        $url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
            array("PATH" => implode("/", ($arParams["CONVERT"] ? $arResult["NAV_CHAIN_UTF8"] : $arResult["NAV_CHAIN"])))); 
    } else {
        $url = WDAddPageParams(str_replace(array("//", "%23"), array("/", "#"), CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], array("PATH" => $redirectTo))), array ('result' => $redirectHilight)); 
    }
    if (isset($_REQUEST["AJAX"]))
    {
		if (!(isset($_REQUEST['redirect']) && $_REQUEST['redirect']=='N'))
		{
			$APPLICATION->RestartBuffer();
			echo "<script>window.location = \"".CUtil::JSEscape($url)."\";</script>";
			die();
		}
    }
    else
    {
        LocalRedirect($url);
    }
endif;
return true;
?>
