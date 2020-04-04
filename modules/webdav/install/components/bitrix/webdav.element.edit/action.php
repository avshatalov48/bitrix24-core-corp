<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$url = (empty($_REQUEST["back_url"]) ? false : $_REQUEST["back_url"]); 
/********************************************************************
				CANCEL
********************************************************************/
if ((!empty($_REQUEST["cancel"])) && check_bitrix_sessid())
{
	$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
	$arResult["NAV_CHAIN_PATH"] = $ob->GetNavChain(array("section_id" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]), true);
	if (!$url)
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
			array(
				"PATH" => implode("/", $arResult["NAV_CHAIN_PATH"]), 
				"SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
				"ELEMENT_ID" => "files", 
				"ELEMENT_NAME" => "files"));
		if ($arParams["ACTION"] == "CLONE")
		{
			$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
				array("ELEMENT_ID" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])));
		}
	}
	if ($_REQUEST["AJAX_CALL"] != "Y" || !empty($_REQUEST["bxajaxid"]))
	{
		LocalRedirect($url);
	}
	
	$APPLICATION->RestartBuffer();
	?><?=CUtil::PhpToJSObject(array("result" => strToLower($arParams["ACTION"]."ed"), "url" => $url));?><?
	die();
}
/********************************************************************
				/ CANCEL
********************************************************************/
/********************************************************************
				AJAX LOCK | UNLOCK
********************************************************************/
if (isset($_REQUEST["ACTION"]) && (in_array($_REQUEST["ACTION"], array("LOCK", "UNLOCK"))))
{

	if (!check_bitrix_sessid())
	{
		$APPLICATION->RestartBuffer();
		CUtil::PhpToJSObject(array("result" => "reload"));
		die();
	}

	$options = array(
		"element_id" => $arParams["ELEMENT_ID"],
		"scope" => "exclusive",
		"type" => "write",
		"owner" => $GLOBALS["USER"]->GetLogin()
	);

	if ($_REQUEST["ACTION"] === "LOCK")
	{
		$res = $ob->LOCK($options); 
		$success = (intval($res) === 200);
	}
	elseif ($_REQUEST["ACTION"] === "UNLOCK")
	{
		$res = $ob->UNLOCK($options);
		$success = (intval($res) === 204);
	}
	

	$res = array();
	$res["LOCK_STATUS"] = CIBlockElement::WF_GetLockStatus($arParams["ELEMENT_ID"], $res['WF_LOCKED_BY'], $res['WF_DATE_LOCK']);
	$lockTill = FormatDate(array(
		"today" => "H:i",
		"" => preg_replace('/:s$/', '', $DB->DateFormatToPHP(CSite::GetDateFormat("FULL"))),
	), MakeTimeStamp($res['WF_DATE_LOCK'])+60*intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60")));

	$status = '';
	if ($res["LOCK_STATUS"] != "green")
	{
		if ($res['WF_LOCKED_BY'] == $USER->GetID())
		{
			$res['LOCKED_USER_NAME'] = $USER->GetFormattedName(false);
		} else {
			$rUser = $USER->GetByID($res["WF_LOCKED_BY"]);
			$arUser = $rUser->Fetch();
			$res['LOCKED_USER_NAME'] = $arUser["NAME"].(strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0?"":" ").$arUser["LAST_NAME"];
		}

		$status .= '<div class="element-status-'.$res['LOCK_STATUS'].'">';
		if ($res['LOCK_STATUS'] == "yellow")
			$status .= '['.GetMessage("IBLOCK_YELLOW_MSG",array('#DATE#' => $lockTill)).']';
		else
			$status .= '['.GetMessage("IBLOCK_RED_MSG",array('#NAME#' => htmlspecialcharsbx($res['LOCKED_USER_NAME']))).']';
		$status .= '</div>';
	} 

	$arResult["NAV_CHAIN_PATH"] = $ob->GetNavChain(array("section_id" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]), true);
	if (!$url)
	{
		if ($arParams["ACTION"] == "CLONE")
		{
			$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
				array("ELEMENT_ID" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])));
		} 
		else 
		{
			$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
				array(
					"PATH" => implode("/", $arResult["NAV_CHAIN_PATH"]), 
					"SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
					"ELEMENT_ID" => "files", 
					"ELEMENT_NAME" => "files"));
		}
	}
	if ($_REQUEST["AJAX_CALL"] != "Y" || !empty($_REQUEST["bxajaxid"]))
	{
		LocalRedirect($url);
	}
	
	$APPLICATION->RestartBuffer();
	?><?=CUtil::PhpToJSObject(array("result" => $res["LOCK_STATUS"], "status" => $status));?><?
	die();
}
/********************************************************************
				/ LOCK
********************************************************************/
$CHILD_ID = false; 
$this->IncludeComponentLang("action.php");

if (!check_bitrix_sessid())
{
	$arError[] = array(
		"id" => "bad_sessid",
		"text" => GetMessage("WD_ERROR_BAD_SESSID"));
}
elseif ($ob->workflow == "bizproc" && strlen($_REQUEST["stop_bizproc"]) > 0)
{
	CBPDocument::TerminateWorkflow($_REQUEST["stop_bizproc"], 
		array("webdav", $arParams["BIZPROC"]["ENTITY"], $arParams["ELEMENT_ID"]), $ar);
	foreach ($ar as $a)
	{
		$arError[] = array(
			"id" => "bizproc", 
			"text" => $a["message"]);
	}
}
elseif ($arParams["ACTION"] == "CLONE")
{
	global $DB;
	$DB->startTransaction();
	$options = array(
		"clone" => true, 
		"PARENT_ELEMENT_ID" => $arParams["ELEMENT_ID"], 
		"FILE_NAME" => $arResult["ELEMENT"]["NAME"], 
		"NAME" => (!empty($_REQUEST["NAME"]) ? $_REQUEST["NAME"] : $arResult["ELEMENT"]["NAME"]), 
		"WF_STATUS_ID" => "2", 
		"PREVIEW_TEXT" => trim($_REQUEST["PREVIEW_TEXT"]),
		"arFile" => $_FILES[$ob->file_prop]); 
	if (!$ob->put_commit($options) || $options["ELEMENT_ID"] <= 0)
	{
		$DB->rollback();
		$arError[] = array(
			"id" => "clone_error",
			"text" => $ob->LAST_ERROR);
	}
	else
	{
		$DB->commit();
		$ob->UNLOCK($tmp = array("element_id" => $arParams["ELEMENT_ID"]));
		$arParams["ELEMENT_ID"] = $options["ELEMENT_ID"];
	}
}
elseif ($arParams["ACTION"] == "DELETE_DROPPED")
{
	$result = $ob->DeleteDroppedFile($arParams["ELEMENT_ID"]);
	$ob->CleanUpDropped();
	if (isset($_REQUEST['AJAX_MODE']))
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		echo CUtil::PhpToJSObject(array('status' => 'success'));
		die();
	}
}
elseif ($arParams["ACTION"] == "DELETE")
{
	$ob->GetMetaID('TRASH');
	$deleteOptions = array("element_id" => $arParams["ELEMENT_ID"]);
	if(!empty($_GET['delete_without_trash']))
	{
		$deleteOptions["force"] = true;
	}
	$result = $ob->DELETE($deleteOptions);
	if (intVal($result) != 204)
	{
		$arError[] = array(
			"id" => "delete_error",
			"text" => GetMessage("WD_ERROR_DELETE"));
	}
	elseif ($ob->workflow != 'workflow' && $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && !$url)
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
			array("ELEMENT_ID" => $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"])); 
	}
}
elseif ($arParams["ACTION"] == "UNDELETE")
{
	$result = $ob->Undelete(array("element_id" => $arParams["ELEMENT_ID"], "dest_url" => $arResult["ELEMENT"]["UNDELETE"])); 
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
			$url = str_replace(array("///", "//"), "/", WDAddPageParams(CComponentEngine::MakePathFromTemplate($arParams["SECTIONS_URL"], 
				array("PATH" => $ob->_get_path($ob->arParams["parent_id"]))), array('result' =>  "doc" . $arParams["ELEMENT_ID"]))); 
		}
	}
}
elseif ($arParams["ACTION"] == "LOCK")
{
	$ob->LOCK($options = array(
		"element_id" => $arParams["ELEMENT_ID"],
		"scope" => "exclusive",
		"type" => "write",
		"owner" => $GLOBALS["USER"]->GetLogin()
	)); 
}
elseif ($arParams["ACTION"] == "UNLOCK")
{
	$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
}
elseif (empty($_REQUEST["NAME"]))
{
	$arError[] = array(
		"id" => "empty_element_name",
		"text" => GetMessage("WD_ERROR_EMPTY_ELEMENT_NAME"));
}
elseif ($ob->workflow == "workflow" && !empty($_POST["WF_STATUS_ID"]) && empty($arResult["WF_STATUSES"][$_POST["WF_STATUS_ID"]]))
{
	$arError["empty_files"] = array(
		"id" => "bad_status",
		"text" => GetMessage("WD_ERROR_BAD_STATUS"));
}
else
{
	$result = $IBLOCK_SECTION_ID = false; 
	
	if (is_set($_REQUEST, "IBLOCK_SECTION_ID") && $_REQUEST["IBLOCK_SECTION_ID"] != $arResult["ELEMENT"]["IBLOCK_SECTION_ID"])
	{
		$IBLOCK_SECTION_ID = intVal($_REQUEST["IBLOCK_SECTION_ID"]); 
		if (!empty($arResult["ROOT_SECTION"]))
		{
			$IBLOCK_SECTION_ID = ($IBLOCK_SECTION_ID <= 0 ? $arResult["ROOT_SECTION"]["ID"] : $IBLOCK_SECTION_ID); 
			if ($arResult["ROOT_SECTION"]["ID"] != $IBLOCK_SECTION_ID)
			{
				$arFilter = array(
					"IBLOCK_ID" => $arParams["IBLOCK_ID"], 
					"ID" => $IBLOCK_SECTION_ID, 
					"RIGHT_MARGIN" => $arResult["ROOT_SECTION"]["RIGHT_MARGIN"], 
					"LEFT_MARGIN" => $arResult["ROOT_SECTION"]["LEFT_MARGIN"]);
				$db_res = CIBlockSection::GetList(array(), $arFilter);
				if (!($db_res && $res = $db_res->Fetch()))
					$IBLOCK_SECTION_ID = false; 
			}
		}
		if ($IBLOCK_SECTION_ID == $arResult["ELEMENT"]["IBLOCK_SECTION_ID"])
			$IBLOCK_SECTION_ID = false; 
	}
	
	$_REQUEST["NAME"] = CWebDavIblock::CorrectName($_REQUEST["NAME"]);
	if ($arResult["ELEMENT"]["FULL_NAME"] != $_REQUEST["NAME"] || $IBLOCK_SECTION_ID !== false)
	{
		$db_res = CIBlockElement::GetList(array(), array(
			"SECTION_ID" => ($IBLOCK_SECTION_ID === false ? $arParams["SECTION_ID"] : $IBLOCK_SECTION_ID), 
			"NAME" => $_REQUEST["NAME"],
			"!=ID" => $arResult["ELEMENT"]["ID"]));
			
		if ($db_res && $res = $db_res->Fetch())
		{
			$arError[] = array(
				"id" => "element_already_exists",
				"text" => GetMessage("WD_ERROR_ELEMENT_ALREADY_EXISTS"));
		} else {
			$rootPath = implode('/', array_slice(explode('/', $arResult['ELEMENT']['PATH']), 0, -1));
			$options = array("path" => $rootPath, "depth" => 1); 
			$result = $ob->PROPFIND($options, $files, array("COLUMNS" => array("ID", "NAME"), "return" => "array")); 
			if (!empty($result["RESULT"]))
			{
				foreach ($result["RESULT"] as $key => $res)
				{
					$options = false; 
					if ($res["ID"] == $arParams["ELEMENT_ID"])
					{
						$options = array(
							"element_id" => $res["ID"], 
							"dest_url" => str_replace("//", "/", $ob->_get_path($_REQUEST["IBLOCK_SECTION_ID"], false) . "/" . $_REQUEST["NAME"]), 
							"overwrite" => true); 
						$res = $ob->MOVE($options); 
					}
					if ($options)
					{
						$oError = $APPLICATION->GetException();
						if (!$oError && intVal($res) >= 300)
						{ 
							$arError[] = array(
								"id" => "bad_move_message", 
								"text" => $res);
						} 
						elseif ($oError)
						{
							$arError[] = array(
								"id" => "bad_move_message", 
								"text" => $oError->GetString());
						}
					}
				}
			}
		}
	}
	$otherChanges = ( 
		($_REQUEST['TAGS'] != $arResult['ELEMENT']['TAGS']) ||
		($_REQUEST['PREVIEW_TEXT'] != $arResult['ELEMENT']['PREVIEW_TEXT']) ||
		(!empty($_REQUEST['WF_COMMENTS'])) ||
		(!empty($_POST['WF_STATUS_ID']) && $_POST['WF_STATUS_ID'] != $arResult['ELEMENT']['WF_STATUS_ID']) ||
		(!empty($_REQUEST['ACTIVE']) && $_REQUEST['ACTIVE'] != $arResult['ELEMENT']['ACTIVE']) ||
		(!empty($_FILES[$ob->file_prop]) && ($_FILES[$ob->file_prop]['error'] == 0) && !empty($_FILES[$ob->file_prop]['tmp_name']))
	);

	if (isset($arResult['ELEMENT']['USER_FIELDS'])
		&& is_array($arResult['ELEMENT']['USER_FIELDS'])
	)
	{
		$arUserFields = array();
		$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields($ob->GetUfEntity(), $arUserFields);

		foreach($arResult['ELEMENT']['USER_FIELDS'] as $fieldCode => $field)
		{
			if (isset($arUserFields[$fieldCode])
				&& $arUserFields[$fieldCode] != $field['VALUE']
			)
			{
				$otherChanges = true;
				break;
			}
		}
		
	}

	if (empty($arError))
	{
		if ($otherChanges)
		{
			$options = Array(
				"ELEMENT_ID" => $arResult["ELEMENT"]["ID"], 
				"NAME" => $_REQUEST["NAME"],
				"FILE_NAME" => $_REQUEST["NAME"], 
				"TAGS" => trim($_REQUEST["TAGS"]), 
				"PREVIEW_TEXT" => trim($_REQUEST["PREVIEW_TEXT"]), 
				"arFile" => $_FILES[$ob->file_prop],
				"USER_FIELDS" => $arUserFields,
			);
				
			if (is_set($_REQUEST, "ACTIVE"))
				$options["ACTIVE"] = ($_REQUEST["ACTIVE"] == "Y" ? "Y" : "N"); 
			if ($IBLOCK_SECTION_ID !== false)
				$options["IBLOCK_SECTION_ID"] = $IBLOCK_SECTION_ID; 
			
			if ($ob->workflow == "workflow")
			{
				$options["WF_COMMENTS"] = $_REQUEST["WF_COMMENTS"];
				if (intVal($_POST["WF_STATUS_ID"]) > 0)
					$options["WF_STATUS_ID"] = $_POST["WF_STATUS_ID"];
			}

			if (!$ob->put_commit($options))
			{
				$arError[] = array(
					"id" => "bad_action_eit",
					"text" => $be->LAST_ERROR);
			}
			else 
			{
				$arParams["ELEMENT_ID"] = $options["ELEMENT_ID"];
				if ($ob->workflow == "bizproc" && $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && 
					(!empty($arResult["ELEMENT"]["~arDocumentStates"]) || $_REQUEST["bizproc_index"] > 0))
				{
					$db_res = CIBlockElement::GetByID($arResult["ELEMENT"]["ID"]); 
					if (!($db_res && $res = $db_res->Fetch()))
					{
						$arParams["ELEMENT_ID"] = $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]; 
					}
				}
				//$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
			}
		} else {
			//$ob->UNLOCK($options = array("element_id" => $arParams["ELEMENT_ID"])); 
		}
	}
}

if (!empty($arError))
	return false; 

if (!$url)
{
	if (!empty($_REQUEST["apply"]))
	{
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_EDIT_URL"], 
			array("ELEMENT_ID" => $arParams["ELEMENT_ID"], "ACTION" => "EDIT"));
		if ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 && $ob->workflow == "bizproc")
		{
			$db_res = CIBlockElement::GetList(array(), array("ID" => $arResult["ELEMENT"]["ID"], "SHOW_NEW" => "Y"));
			if (!($db_res && $res = $db_res->Fetch()))
				$url = false; 
		}
	}
	if (!$url && $ob->workflow == "bizproc" && $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 || 
		$arParams["ACTION"] == "CLONE")
	{
		//$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_VERSIONS_URL"], 
			//array("ELEMENT_ID" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])));
		$url = CComponentEngine::MakePathFromTemplate($arParams["~ELEMENT_URL"], 
			array("ELEMENT_ID" => ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0 ? $arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] : $arResult["ELEMENT"]["ID"])));
		$url = WDAddPageParams($url, array('webdavForm'.$arParams["IBLOCK_ID"].'_active_tab' => 'tab_version'));
	}
	if (!$url)
	{
		$arResult["NAV_CHAIN_PATH"] = $ob->GetNavChain(array("section_id" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"]), true);
		$url = CComponentEngine::MakePathFromTemplate($arParams["~SECTIONS_URL"], 
			array(
				"PATH" => implode("/", $arResult["NAV_CHAIN_PATH"]), 
				"SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
				"ELEMENT_ID" => "files", 
				"ELEMENT_NAME" => "files"));
	}
}

if ($_REQUEST["AJAX_CALL"] == "Y" || !empty($_REQUEST["bxajaxid"]))
{
	$APPLICATION->RestartBuffer();
	?><?=CUtil::PhpToJSObject(array("result" => strToLower($arParams["ACTION"]."ed"), "url" => $url));?><?
	die();
}
if (in_array($arParams["ACTION"], array("LOCK", "UNLOCK")))
	$url = WDAddPageParams($url, array('result' => 'doc' . $arParams["ELEMENT_ID"]));
if ($arParams["ACTION"] == "DELETE")
	$url = WDAddPageParams($url, array("result" => "deleted"));
LocalRedirect($url);
?>
