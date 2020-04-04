<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/webdav/templates/.default/script.js");
endif;
function ___WDBPStartWorkflowParametersShow($templateId, $arWorkflowParameters, $bVarsFromForm, &$arFields)
{
	$templateId = intval($templateId);
	if ($templateId <= 0)
		return;

	if (!isset($arWorkflowParameters) || !is_array($arWorkflowParameters))
		$arWorkflowParameters = array();

	$arParametersValues = array();
	$keys = array_keys($arWorkflowParameters);
	foreach ($keys as $key)
	{
		$v = ($bVarsFromForm ? $_REQUEST["bizproc".$templateId."_".$key] : $arWorkflowParameters[$key]["Default"]);
		if (!is_array($v))
		{
			$arParametersValues[$key] = htmlspecialcharsbx($v);
		}
		else
		{
			$keys1 = array_keys($v);
			foreach ($keys1 as $key1)
				$arParametersValues[$key][$key1] = htmlspecialcharsbx($v[$key1]);
		}
	}
	foreach ($arWorkflowParameters as $parameterKey => $arParameter)
	{
		$parameterKeyExt = "bizproc".$templateId."_".$parameterKey;
		
		$sData = GetMessage("BPCGDOC_INVALID_TYPE"); 
		switch ($arParameter["Type"])
		{
			case "int":
			case "double":
				$sData = '<input type="text" name="'.$parameterKeyExt.'" size="10" value="'.$arParametersValues[$parameterKey].'" />'; 
				break;
			case "string":
				$sData = '<input type="text" name="'.$parameterKeyExt.'" size="50" value="'.$arParametersValues[$parameterKey].'" />'; 
				break;
			case "text":
				$sData = '<textarea name="'.$parameterKeyExt.'" rows="5" cols="40">'.$arParametersValues[$parameterKey].'</textarea>'; 
				break;
			case "select":
				$sData = '<select name="'.$parameterKeyExt.($arParameter["Multiple"] ? '[]" size="5" multiple="multiple"' : '"').'>'; 
				if (is_array($arParameter["Options"]) && count($arParameter["Options"]) > 0)
				{
					foreach ($arParameter["Options"] as $key => $value)
					{
						$sData .= '<option value="' . $key . '"' . 
							((!$arParameter["Multiple"] && $key == $arParametersValues[$parameterKey] || $arParameter["Multiple"] && is_array($arParametersValues[$parameterKey]) && in_array($key, $arParametersValues[$parameterKey])) ? ' selected="selected"' : '' ). '>' . $value . '</option>'; 
					}
				}
				$sData .= '</select>'; 
				break;
			case "bool":
				$sData = '<select name="'. $parameterKeyExt .'">'.
						'<option value="Y"'. (($arParametersValues[$parameterKey] == "Y") ? ' selected="selected"' : '' ).'>'. GetMessage("WD_Y") .'</option>'.
						'<option value="N"'. (($arParametersValues[$parameterKey] == "N") ? ' selected="selected"' : '' ).'>'. GetMessage("WD_N") .'</option>'.
					'</select>'; 
				break;
			case "date":
			case "datetime":
				$sData = CAdminCalendar::CalendarDate($parameterKeyExt, $arParametersValues[$parameterKey], 19, ($arParameter["Type"] == "date"));
				break;
			case "user":
				$sData = '<textarea name="'. $parameterKeyExt .'" id="id_'. $parameterKeyExt .'" rows="3" cols="40">' .
					$arParametersValues[$parameterKey] . '</textarea><input type="button" value="..." onclick="BPAShowSelector(\'id_'. $parameterKeyExt . '\', \'user\');" />'; 
				break;
		}
		$arFields[] = array(
			"id" => $parameterKeyExt, 
			"required" => $arParameter["Required"], 
			"name" => $arParameter["Name"], 
			"tooltip" => trim($arParameter["Description"]), 
			"type" => "custom", 
			"value" => $sData); 
	}
}

/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arCurrentUserGroups = $arResult["CurrentUserGroups"];
/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;
__prepare_item_info($arResult["ELEMENT"], ($arParams + array("TEMPLATES" => array())));
$aCols = __build_item_info($arResult["ELEMENT"], ($arParams + array("TEMPLATES" => array()))); 
$aCols = $aCols["columns"]; 

$arCustomFields = array(); 
$arFields = array(
	array("id" => "FILE_TITLE", "name" => GetMessage("WD_FILE"), "type" => "label", "value" => $aCols["NAME"])
);

if ($arParams["ACTION"] == "CLONE")
{
	$arFields = array(
		array("id" => "FILE_TITLE", "name" => GetMessage("WD_ORIGINAL"), "type" => "label", "value" => $aCols["NAME"])
	);
	$arFields[] = 
		array("id" => "NAME", "name" =>GetMessage("WD_NAME"), "required" => true, "type" => "text", "value" => $arResult["ELEMENT"]["NAME"]); 
	$arFields[] = array("id" => $arParams["NAME_FILE_PROPERTY"], "name" => GetMessage("WD_FILE_REPLACE"), "type" => "custom", 
		"value" => '<input type="file" name="'.$arParams["NAME_FILE_PROPERTY"].'" value="" />'); 

	ob_start();
	if(CModule::IncludeModule("fileman"))
	{			
		$ar = array(
			'width' => '520',
			'height' => '200',
			'inputName' => 'PREVIEW_TEXT',
			'inputId' => 'PREVIEW_TEXT',
			'jsObjName' => 'pLEditorDav',
			'content' => trim($arResult["ELEMENT"]["~PREVIEW_TEXT"]),
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'bArisingToolbar' => false,
			'bResizable' => true,
			'bSaveOnBlur' => true,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'RemoveFormat',
				'Header', 'intenalLink', 'CreateLink', 'DeleteLink', 'ImageLink', 'ImageUpload', 'Category', 'Table',
				'BackColor', 'ForeColor',
				'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent',
				'Signature'
			)
		);
		$LHE = new CLightHTMLEditor;
		$LHE->Show($ar);			
	}
	$lhe = ob_get_clean();

	$arFields[] = array("id" => "PREVIEW_TEXT", "name" => GetMessage("WD_DESCRIPTION"), "type" => "custom", "value" => $lhe); 

	$arTabs = array(
		array("id" => "tab_main", "name" => GetMessage("WD_VERSION"), "title" => GetMessage("WD_VERSION_ALT"), "fields" => $arFields)); 

	if ($arParams["OBJECT"]->workflow == "bizproc")
	{
		$bizProcIndex = $bizProcCounter = 0;
		$arDocumentStates = CBPDocument::GetDocumentStates(
			$arParams["DOCUMENT_TYPE"], 
			null);
		if (!empty($arDocumentStates))	
		{
			$arCurrentUserGroups[] = "Author"; 
			$arFields = array(); 
			CBPDocument::AddShowParameterInit("webdav", "only_users", $arParams["BIZPROC"]["DOCUMENT_TYPE"], $arParams["BIZPROC"]["ENTITY"]);
			foreach ($arDocumentStates as $key => $arDocumentState)
			{
				$bizProcIndex++;
				$canViewWorkflow = CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::ViewWorkflow,
					$GLOBALS["USER"]->GetID(),
					$arParams["DOCUMENT_ID"],
					array(
						"DocumentType" => $arParams["BIZPROC"]["DOCUMENT_TYPE"], 
						"IBlockPermission" => $arParams["PERMISSION"], 
						"AllUserGroups" => $arCurrentUserGroups, 
						"DocumentStates" => $arDocumentStates, 
						"WorkflowId" => ($arDocumentState["ID"] > 0 ? $arDocumentState["ID"] : $arDocumentState["TEMPLATE_ID"])));
				if (!$canViewWorkflow)
					continue;
				$bizProcCounter++;
				
				$arFieldTmp = array(); 
				if (strlen($arDocumentState["ID"]) <= 0)
				{
					___WDBPStartWorkflowParametersShow($arDocumentState["TEMPLATE_ID"], 
						$arDocumentState["TEMPLATE_PARAMETERS"], ($_SERVER['REQUEST_METHOD'] == "POST"), 
						$arFieldTmp);
				}
				
				$arEvents = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentState);
				if (count($arEvents) > 0)
				{
					$sData = 
						'<input type="hidden" name="bizproc_id_' . $bizProcIndex . '" value="' . $arDocumentState["ID"] .'" />'.
						'<input type="hidden" name="bizproc_template_id_'. $bizProcIndex .'" value="'. $arDocumentState["TEMPLATE_ID"] .'" />'.
						'<select name="bizproc_event_' . $bizProcIndex . '">'.
							'<option value="">' . GetMessage("IBEL_BIZPROC_RUN_CMD_NO") . '</option>';
					foreach ($arEvents as $e)
					{
						$sData .= '<option value="'. htmlspecialcharsbx($e["NAME"]).'"'.
							(($_REQUEST["bizproc_event_".$bizProcIndex] == $e["NAME"]) ? ' selected="selected"' : '').'>' . 
								htmlspecialcharsbx($e["TITLE"]) . '</option>'; 
					}
					$sData .= '</select>'; 
					$arFieldTmp[] = array(
						"id" => "BIZPROC_EVENTS_".$arDocumentState["TEMPLATE_ID"], 
						"name" => GetMessage("IBEL_BIZPROC_RUN_CMD"), 
						"type" => "custom", 
						"value" => $sData); 
				}
				
				$arFields[] = array("id" => "BIZPROC_".$arDocumentState["STATE_NAME"], "name" => htmlspecialcharsbx($arDocumentState["TEMPLATE_NAME"]), "type" => "section"); 
				$arFields = array_merge($arFields, $arFieldTmp);
				
			}
			$arCustomFields[] = '<input type="hidden" name="bizproc_index" value="'.$bizProcIndex.'" />'; 
			
			$arTabs[] = array("id" => "tab_workflow", "name" => GetMessage("IBEL_E_TAB_BIZPROC"), "title" => GetMessage("IBEL_E_TAB_BIZPROC"), "fields" => $arFields); 
		}
	}
}
else
{
	$arFields = array(
		array("id" => "FILE_TITLE", "name" => GetMessage("WD_FILE"), "type" => "label", "value" => $aCols["NAME"])
	);
	$arFields[] = array("id" => "CREATED", "name" => GetMessage("WD_FILE_CREATED"), "type" => "label", "value" => $arResult["ELEMENT"]["DATE_CREATE"].' '.$aCols["CREATED_BY"]); 
	$arFields[] = array("id" => "UPDATED", "name" => GetMessage("WD_FILE_MODIFIED"), "type" => "label", "value" => $arResult["ELEMENT"]["TIMESTAMP_X"].' '.$aCols["MODIFIED_BY"]); 
	$arFields[] = array("id" => "FILE_SIZE", "name" => GetMessage("WD_FILE_SIZE"), "type" => "label", "value" => $arResult["ELEMENT"]["FILE_SIZE"].
		' <span class="wd-item-controls element_download">'.
			'<a target="_blank" href="'.$arResult["ELEMENT"]["URL"]["DOWNLOAD"].'">'.GetMessage("WD_DOWNLOAD_FILE").'</a></span>'); 
	if (!empty($arResult["ELEMENT_ORIGINAL"]))
	{
		$aCols2 = __build_item_info($arResult["ELEMENT_ORIGINAL"], ($arParams + array("TEMPLATES" => array()))); 
		$aCols2 = $aCols2["columns"]; 
	
		$arFields[] = 
		array("id" => "FILE_TITLE_ORIGINAL", "name" => GetMessage("WD_ORIGINAL"), "type" => "label", "value" => $aCols2["NAME"]); 
	}
	
	$arFields[] = 
		array("id" => "NAME", "name" =>GetMessage("WD_NAME"), "required" => true, "type" => "text", "value" => $arResult["ELEMENT"]["NAME"]); 
	$arFields[] = array("id" => "TAGS", "name" => GetMessage("WD_TAGS"), "type" => "custom"); 
	$arData["TAGS"] = '<input type="text" name="TAGS" value="'.$arResult["ELEMENT"]["TAGS"].'" />'; 
	if (IsModuleInstalled("search"))
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:search.tags.input", 
			"", 
			array(
				"VALUE" => $arResult["ELEMENT"]["~TAGS"], 
				"NAME" => "TAGS"), 
			null,
			array("HIDE_ICONS" => "Y"));
		$arData["TAGS"] = ob_get_clean(); 
	}
	
	ob_start();
	if(CModule::IncludeModule("fileman"))
	{			
		$ar = array(
			'width' => '520',
			'height' => '200',
			'inputName' => 'PREVIEW_TEXT',
			'inputId' => 'PREVIEW_TEXT',
			'jsObjName' => 'pLEditorDav',
			'content' => trim($arResult["ELEMENT"]["~PREVIEW_TEXT"]),
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'bArisingToolbar' => false,
			'bResizable' => true,
			'bSaveOnBlur' => true,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'RemoveFormat',
				'Header', 'intenalLink', 'CreateLink', 'DeleteLink', 'ImageLink', 'ImageUpload', 'Category', 'Table',
				'BackColor', 'ForeColor',
				'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent',
				'Signature'
			)
		);
		$LHE = new CLightHTMLEditor;
		$LHE->Show($ar);			
	}
	$lhe = ob_get_clean();

	$arFields[] = array("id" => "PREVIEW_TEXT", "name" => GetMessage("WD_DESCRIPTION"), "type" => "custom", "value" => $lhe); 

	$arFields[] = array("id" => $arParams["NAME_FILE_PROPERTY"], "name" => GetMessage("WD_FILE_REPLACE"), "type" => "custom", 
		"value" => '<input type="file" name="'.$arParams["NAME_FILE_PROPERTY"].'" value="" />'); 
	
	$arParams["OBJECT"]->SetPath($arResult["ELEMENT"]["PATH"]);
	if ($arParams['OBJECT']->meta_state != 'TRASH')
	{
		$arFields[] = array("id" => "IBLOCK_SECTION_ID", "name" => GetMessage("WD_PARENT_SECTION"), "type" => "custom"); 
		$arData["IBLOCK_SECTION_ID"] = '<select name="IBLOCK_SECTION_ID">'.
			'<option value="0"'.
			($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == 0 ? ' selected=selected"' : '').
			($arResult["~ELEMENT"]["IBLOCK_SECTION_ID"] <= 0 ? ' class="selected"' : '').'>'.GetMessage("WD_CONTENT").'</option>'; 
		foreach ($arResult["SECTION_LIST"] as $res)
		{
			$arData["IBLOCK_SECTION_ID"] .= 
				'<option value="'.$res["ID"].'"'.
				($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == $res["ID"] ? ' selected=selected"' : '').
				($arResult["~ELEMENT"]["IBLOCK_SECTION_ID"] == $res["ID"] ? ' class="selected"' : '').'>'.str_repeat(".", $res["DEPTH_LEVEL"]).($res["NAME"]).'</option>'; 
		}
		$arData["IBLOCK_SECTION_ID"] .= '</select>'; 
	}

	$arTabs = array(
		array("id" => "tab_main", "name" => GetMessage("WD_DOCUMENT"), "title" => GetMessage("WD_DOCUMENT_ALT"), "fields" => $arFields)); 

	$arFields = array(); 
	if ($arParams["USE_WORKFLOW"] == "Y")
	{
		if ($arParams["SHOW_WORKFLOW"] != "N")
		{
			$arFields[] = array("id" => "WF_STATUS_ID", "name" => GetMessage("WD_FILE_STATUS"), "type" => "custom"); 
			$arData["WF_STATUS_ID"] = 
				'<select name="WF_STATUS_ID">'; 
			foreach ($arResult["WF_STATUSES"] as $key => $val)
			{
				$arData["WF_STATUS_ID"] .= 
					'<option value="'.$key.'"'.($key == $arResult["ELEMENT"]["WF_STATUS_ID"] ? ' selected="selected"' : '').'>'.htmlspecialcharsEx($val).'</option>'; 
			}
			$arData["WF_STATUS_ID"] .= 
				'</select>'; 
		}
		
		$arFields[] = array("id" => "WF_COMMENTS", "name" => GetMessage("WD_FILE_COMMENTS"), "type" => "custom"); 
		$arData["WF_COMMENTS"] = '<textarea name="WF_COMMENTS">'.htmlspecialcharsEx($_REQUEST["WF_COMMENTS"]).'</textarea>'; 
		$arTabs[] = array("id" => "tab_workflow", "name" => GetMessage("WD_WF"), "title" => GetMessage("WD_WF_PARAMS"), "fields" => $arFields); 
	}
	elseif ($arParams["USE_BIZPROC"] == "Y")
	{
		$arFields[] = array("id" => "BP_PUBLISHED", "name" => GetMessage("IBEL_E_PUBLISHED"), "type" => "label"); 
		$arData["BP_PUBLISHED"] = ($arResult["ELEMENT"]["BP_PUBLISHED"] == "Y" ? GetMessage("WD_Y") : GetMessage("WD_N")); 
		
		CBPDocument::AddShowParameterInit("webdav", "only_users", $arParams["BIZPROC"]["DOCUMENT_TYPE"], $arParams["BIZPROC"]["ENTITY"]);
		$bizProcCounter = $bizProcIndex = 0;
		if (!empty($arResult["ELEMENT"]["~arDocumentStates"]))	
		{
			foreach ($arResult["ELEMENT"]["~arDocumentStates"] as $key => $arDocumentState)
			{
				$arFieldTmp = array(); 
				$arDataTmp = array(); 
				$bizProcIndex++;
				if ((strlen($arDocumentState["WORKFLOW_STATUS"]) <= 0 && $arDocumentState["ID"] > 0) || 
					(strlen($arDocumentState["ID"]) > 0 && !$arDocumentState["ViewWorkflow"]))
					continue;
	
				$bizProcCounter++;
			
				$proc = array(
					"title" => "");
			
				$id = (strlen($arDocumentState["ID"]) > 0 ? $arDocumentState["ID"] : $arDocumentState["STATE_NAME"]); 
				if (strlen($arDocumentState["STATE_NAME"]) > 0 || 
					(strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0))
				{
					$sData = (strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]); 
					if (strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0)
					{
						$sData .= ' [<a href="'.$APPLICATION->GetCurPageParam("edit=Y&stop_bizproc=".$arDocumentState["ID"]."&".bitrix_sessid_get(), 
							array("stop_bizproc", "sessid", "edit")).'">'.GetMessage("IBEL_BIZPROC_STOP").'</a>]'; 
					}
					if (strlen($arDocumentState["ID"]) > 0)
					{
						$sData .= ' [<a href="'.CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_BIZPROC_LOG_URL"], 
								array("ID" => $arDocumentState["ID"], "SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
									"ELEMENT_ID" => $arParams["ELEMENT_ID"])).'">'.GetMessage("WD_HISTORY").'</a>]'; 
					}
					$arFieldTmp[] = array("id" => "BIZPROC_STATE_".$id, "name" => GetMessage("IBEL_BIZPROC_STATE"), "type" => "label", "value" => $sData); 
				}
				
				if (strlen($arDocumentState["ID"]) <= 0)
				{
					___WDBPStartWorkflowParametersShow($arDocumentState["TEMPLATE_ID"],
						$arDocumentState["TEMPLATE_PARAMETERS"], ($_SERVER['REQUEST_METHOD'] == "POST"), 
						$arFieldTmp);
				}
				
				$arEvents = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentState);
				if (count($arEvents) > 0)
				{
					$sData = 
						'<input type="hidden" name="bizproc_id_' . $bizProcIndex . '" value="' . $arDocumentState["ID"] .'" />'.
						'<input type="hidden" name="bizproc_template_id_'. $bizProcIndex .'" value="'. $arDocumentState["TEMPLATE_ID"] .'" />'.
						'<select name="bizproc_event_' . $bizProcIndex . '">'.
							'<option value="">' . GetMessage("IBEL_BIZPROC_RUN_CMD_NO") . '</option>';
					foreach ($arEvents as $e)
					{
						$sData .= 
						'<option value="'. htmlspecialcharsbx($e["NAME"]).'"'.(($_REQUEST["bizproc_event_".$bizProcIndex] == $e["NAME"]) ? ' selected="selected"' : '').'>' .
								htmlspecialcharsbx($e["TITLE"]) . '</option>'; 
					}
					$sData .= '</select>'; 
					$arFieldTmp[] = array("id" => "BIZPROC_EVENTS_".$id, "name" => GetMessage("IBEL_BIZPROC_RUN_CMD"), "type" => "custom", "value" => $sData); 
				}
	
				if (strlen($arDocumentState["ID"]) > 0)
				{
					$arTasks = CBPDocument::GetUserTasksForWorkflow($USER->GetID(), $arDocumentState["ID"]);
					if (count($arTasks) > 0)
					{
						$sData = ""; 
						foreach ($arTasks as $arTask)
						{
							$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"], array("ID" => $arTask["ID"]));
							$url .= (strpos($url, "?") === false ? "?" : "&")."back_url=".urlencode($APPLICATION->GetCurPageParam("", array()));
							$sData .= '<a href="'.$url.'" title="'.str_replace(array("&amp;quot;", "&quot;"), "", htmlspecialcharsbx($arTask["DESCRIPTION"])).'">'. $arTask["NAME"] .'</a><br />'; 
						}
						$arFieldTmp[] = array("id" => "BIZPROC_TASKS_".$id, "name" => GetMessage("IBEL_BIZPROC_TASKS"), "type" => "custom", "value" => $sData); 
					}
				}
				if (!empty($arFieldTmp))
				{
					$arFields[] = array("id" => "BIZPROC_".$id, "name" => htmlspecialcharsbx($arDocumentState["TEMPLATE_NAME"]), "type" => "section"); 
					$arFields = array_merge($arFields, $arFieldTmp);
				}
			}
		}
		$arCustomFields[] = '<input type="hidden" name="bizproc_index" value="'.$bizProcIndex.'" />'; 
		
		$arTabs[] = array("id" => "tab_workflow", "name" => GetMessage("IBEL_E_TAB_BIZPROC"), "title" => GetMessage("IBEL_E_TAB_BIZPROC"), "fields" => $arFields); 
	}
}
?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID" => $arParams["FORM_ID"],
		"TABS" => $arTabs,
		"BUTTONS" => ($arResult["WRITEABLE"] == "Y" ? array(
			"back_url" => $APPLICATION->GetCurPageParam("cancel=Y&edit=Y&".bitrix_sessid_get(), array("cancel", "edit")), 
			"custom_html" => '<input type="hidden" name="ELEMENT_ID" value="'.$arParams["ELEMENT_ID"].'" />'.
				'<input type="hidden" name="edit" value="Y" />'.
				'<input type="hidden" name="ACTION" value="'.$arParams["ACTION"].'" />'.
				implode('', $arCustomFields)
			) : null),
		"DATA"=> $arData,
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);

if ($this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = (is_array($this->__component->__parent->arResult["arButtons"]) ? 
		$this->__component->__parent->arResult["arButtons"] : array()); 
	if ($arParams["USE_BIZPROC"] == "Y")
	{
		$this->__component->__parent->arResult["arButtons"]["versions"] = array(
			"TEXT" => GetMessage("WD_VERSIONS"),
			"TITLE" => str_replace("#NAME#", $arResult["ELEMENT_ORIGINAL"]["NAME"], GetMessage("WD_VERSIONS_ALT")),
			"LINK" => $arResult["ELEMENT"]["URL"]["VERSIONS"],
			"ICON" => "btn-list"); 
		if ($arParams["ACTION"] != "CLONE" && $arResult["ELEMENT"]["SHOW"]["BP"] == "Y")
			$this->__component->__parent->arResult["arButtons"]["bizproc"] = array(
				"TEXT" => GetMessage("WD_DOCUMENT_BP"),
				"TITLE" => (intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0 ? GetMessage("WD_DOCUMENT_BP_ALT") : GetMessage("WD_DOCUMENT_BP_ALT2")),
				"LINK" => $arResult["URL"]["BP"],
				"ICON" => "btn-list element-bp"); 
	}

	if ($arParams["ACTION"] != "CLONE" && 
		($arResult["ELEMENT"]["SHOW"]["HISTORY"] == "Y" || $arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y"))
	{
		$this->__component->__parent->arResult["arButtons"][] = array("NEWBAR" => true); 

		if ($arResult["ELEMENT"]["SHOW"]["HISTORY"] == "Y")
			$this->__component->__parent->arResult["arButtons"]["history"] = array(
				"TEXT" => GetMessage("WD_HISTORY_FILE"),
				"TITLE" => (intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0 ? GetMessage("WD_HISTORY_FILE_ALT") : GetMessage("WD_HISTORY_FILE_ALT2")),
				"LINK" => $arResult["ELEMENT"]["URL"]["HIST"],
				"ICON" => "btn-list element-history"); 
		if ($arResult["WRITEABLE"] == "Y" && $arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y")
			$this->__component->__parent->arResult["arButtons"]["delete"] = array(
				"TEXT" => GetMessage("WD_DELETE_FILE"),
				"TITLE" => (intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0 ? GetMessage("WD_DELETE_FILE_ALT") : GetMessage("WD_DELETE_FILE_ALT2")),
				"LINK" => "javascript:WDDrop('".CUtil::JSEscape($arResult["ELEMENT"]["URL"]["DELETE"])."');",
				"ICON" => "btn-delete element-delete"); 
	}
}
?>
