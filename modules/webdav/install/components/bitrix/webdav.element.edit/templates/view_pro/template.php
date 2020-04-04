<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/webdav/templates/.default/script.js");
endif;
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/public_tools.js');
CUtil::InitJSCore(array('window', 'ajax'));
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
if (!empty($arResult["NOTIFY_MESSAGE"])):
	ShowNote($arResult["NOTIFY_MESSAGE"]);
endif;
$uploadUrl = $arResult["ELEMENT"]["URL"]["UPLOAD"];
__prepare_item_info($arResult["ELEMENT"], $arParams);
$arResult["ELEMENT"]["URL"]["UPLOAD"] = $uploadUrl;
$aCols = __build_item_info($arResult["ELEMENT"], $arParams); 
$aCols = $aCols["columns"]; 
$arCustomFields = array();

$elementPreviewText = "";
if(isset($arResult["ELEMENT"]["PREVIEW_TEXT"]))
{
	$Sanitizer = new CBXSanitizer;
	$Sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
	$elementPreviewText = $Sanitizer->SanitizeHtml($arResult["ELEMENT"]["PREVIEW_TEXT"]);
}
//$arResult["ELEMENT"]["~PREVIEW_TEXT"]

if ($arParams["ACTION"] == "CLONE")
{
	$arFields = array(
		array("id" => "FILE_TITLE", "name" => GetMessage("WD_ORIGINAL"), "type" => "label", "value" => $aCols["NAME"])
	);
	$arFields[] = 
		array("id" => "NAME", "name" =>GetMessage("WD_NAME"), "required" => true, "type" => "text", "value" => $arResult["ELEMENT"]["NAME"]); 
	$arFields[] = array("id" => $arParams["NAME_FILE_PROPERTY"], "name" => GetMessage("WD_FILE_REPLACE"), "type" => "custom", 
		"value" => '<input type="file" name="'.$arParams["NAME_FILE_PROPERTY"].'" value="" />'); 
	$arFields[] = array("id" => "PREVIEW_TEXT", "name" => GetMessage("WD_DESCRIPTION"), "type" => "textarea", 
		"value" => $elementPreviewText); 
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

				$arFields[] = array("id" => "BIZPROC_".$arDocumentState["STATE_NAME"], "name" => $arDocumentState["TEMPLATE_NAME"], "type" => "section"); 
				$arFields = array_merge($arFields, $arFieldTmp);

			}
			$arCustomFields[] = '<input type="hidden" name="bizproc_index" value="'.$bizProcIndex.'" />'; 

			$arTabs[] = array("id" => "tab_workflow", "name" => GetMessage("IBEL_E_TAB_BIZPROC"), "title" => GetMessage("IBEL_E_TAB_BIZPROC"), "fields" => $arFields); 
		}
	}
}
else
{
	$arFields = array();
	$addFileSize = "<span class=\"wd-file-size\">(".$arResult["ELEMENT"]["FILE_SIZE"].")</span>";


$destUrl = $APPLICATION->GetCurPageParam("", array("MID", "result"));
if (isset($arParams["FORM_ID"]) && isset($arParams["TAB_ID"]))
	$destUrl = $APPLICATION->GetCurPageParam($arParams["FORM_ID"]."_active_tab=".$arParams["TAB_ID"], array("MID", "result", $arParams["FORM_ID"]."_active_tab"));


	$addButtons = bitrix_sessid_post()."
<input type=\"hidden\" name=\"ELEMENT_ID\" value=\"".$arParams["ELEMENT_ID"]."\" />
<input type=\"hidden\" name=\"edit\" value=\"Y\" />
<input type=\"hidden\" name=\"back_url\" value=\"".htmlspecialcharsbx($destUrl)."\" />
<input type=\"hidden\" name=\"ACTION\" value=\"".$arParams["ACTION"]."\" />
<div class=\"wd-buttons\">
</div>
";
	$addDownload = '<span class="wd-item-controls element_download" style="float:none; "><a target="_blank" href="'.$arResult["ELEMENT"]["URL"]["DOWNLOAD"].'">'.GetMessage("WD_DOWNLOAD_FILE").'</a></span>';

	$arFields[] = array("id" => "NAME", "name" => GetMessage("WD_FILE"), "type" => "custom", "value" => 
		"<div class=\"quick-view wd-toggle-edit wd-file-name\">".$aCols["NAME"].$addFileSize.$addDownload."</div><input class=\"quick-edit wd-file-name\" type=\"text\" name=\"NAME\" value=\"".$arResult["ELEMENT"]["NAME"]."\"/>".$addButtons);

	if ($arParams["SHOW_RATING"] == "Y")
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:rating.vote", $arParams["RATING_TYPE"],
			Array(
				"ENTITY_TYPE_ID" => "IBLOCK_ELEMENT",
				"ENTITY_ID" => $arResult["ELEMENT"]["ID"],
				"OWNER_ID" => $arResult["ELEMENT"]["CREATED_BY"]['ID'],
				"PATH_TO_USER_PROFILE" => $arParams["USER_VIEW_URL"],
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arFields[] = array(
			"id" => "RATING",
			"name" => GetMessage("WD_RATING"),
			"type" => "label",
			"value" => $sVal
		);
	}

	$createdUser = "<div class=\"wd-modified-empty\">&nbsp;</div>";
	if ($arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]['ID']]['ID'] > 0)
	{
		ob_start();
		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"ID" => $arResult["ELEMENT"]["CREATED_BY"]['ID'],
				"HTML_ID" => "group_mods_".$arResult["ELEMENT"]["CREATED_BY"]['ID'],
				"NAME" => $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]['ID']]["NAME"],
				"LAST_NAME" => $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]['ID']]["LAST_NAME"],
				"SECOND_NAME" => $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]['ID']]["SECOND_NAME"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"LOGIN" => $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]['ID']]["LOGIN"],
				"PROFILE_URL" => $pu,
				"USE_THUMBNAIL_LIST" => "Y",
				"THUMBNAIL_LIST_SIZE" => 28,
				"DESCRIPTION" => FormatDateFromDB($arResult["ELEMENT"]["DATE_CREATE"]),
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);
		$createdUser = ob_get_clean();
	}

	$modifiedUser = "<div class=\"wd-modified-empty\">&nbsp;</div>";
	if (($arResult["ELEMENT"]["MODIFIED_BY"]['ID'] == $arResult["ELEMENT"]["CREATED_BY"]['ID']) &&
		($arResult["ELEMENT"]["DATE_CREATE"] == $arResult["ELEMENT"]["TIMESTAMP_X"]))
	{
		$arFields[] = array("id" => "CREATED", "name" => GetMessage("WD_FILE_CREATED"), "type" => "label", "value" => "<div class=\"wd-created wd-modified\">".$createdUser.$modifiedUser."</div>"); 
	} else {
		if ($arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]['ID']]['ID'] > 0)
		{
		ob_start();
		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"ID" => $arResult["ELEMENT"]["MODIFIED_BY"]['ID'],
				"HTML_ID" => "group_mods_".$arResult["ELEMENT"]["MODIFIED_BY"]['ID'],
				"DESCRIPTION" => FormatDateFromDB($arResult["ELEMENT"]["TIMESTAMP_X"]),
				"NAME" => $arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]['ID']]["NAME"],
				"LAST_NAME" => $arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]['ID']]["LAST_NAME"],
				"SECOND_NAME" => $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]['ID']]["SECOND_NAME"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"LOGIN" => $arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]['ID']]["LOGIN"],
				"PROFILE_URL" => $pu,
				"USE_THUMBNAIL_LIST" => "Y",
				"THUMBNAIL_LIST_SIZE" => 28,
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);
		$modifiedUser = ob_get_clean();
		}
		$arFields[] = array("id" => "CREATED", "name" => GetMessage("WD_FILE_CREATED"), "type" => "label", "value" => "<div class=\"wd-created\">".$createdUser."</div>"); 
		$arFields[] = array("id" => "UPDATED", "name" => GetMessage("WD_FILE_MODIFIED"), "type" => "label", "value" => "<div class=\"wd-modified\">".$modifiedUser."</div>"); 
	}
	if (!empty($arResult["ELEMENT_ORIGINAL"]))
	{
		$aCols2 = __build_item_info($arResult["ELEMENT_ORIGINAL"], ($arParams + array("TEMPLATES" => array()))); 
		$aCols2 = $aCols2["columns"];

		$arFields[] =
		array("id" => "FILE_TITLE_ORIGINAL", "name" => GetMessage("WD_ORIGINAL"), "type" => "label", "value" => $aCols2["NAME"]); 
	}

	// parent folder select control
	$arParams["OBJECT"]->SetPath($arResult["ELEMENT"]["PATH"]);
	if ($arParams['OBJECT']->meta_state != 'TRASH')
	{
		$arFields[] = array("id" => "IBLOCK_SECTION_ID", "name" => GetMessage("WD_PARENT_SECTION"), "type" => "custom"); 
		$arData["IBLOCK_SECTION_ID"] = '<select class="quick-edit" name="IBLOCK_SECTION_ID">'.
			'<option value="0"'.
			($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == 0 ? ' selected=selected"' : '').
			($arResult["~ELEMENT"]["IBLOCK_SECTION_ID"] <= 0 ? ' class="selected"' : '').'>'.GetMessage("WD_CONTENT").'</option>'; 
		$sectionName = GetMessage("WD_CONTENT");
		foreach ($arResult["SECTION_LIST"] as $res)
		{
			$arData["IBLOCK_SECTION_ID"] .=
				'<option value="'.$res["ID"].'"'.
				($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == $res["ID"] ? ' selected=selected"' : '').
				($arResult["~ELEMENT"]["IBLOCK_SECTION_ID"] == $res["ID"] ? ' class="selected"' : '').'>'.str_repeat(".", $res["DEPTH_LEVEL"]).($res["NAME"]).'</option>'; 
			if ($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == $res["ID"])
				$sectionName = str_repeat(".", $res["DEPTH_LEVEL"]).($res["NAME"]);
		}
		$arData["IBLOCK_SECTION_ID"] .= '</select>';
		$arData["IBLOCK_SECTION_ID"] = "<div class=\"quick-view wd-toggle-edit wd-section\">".$sectionName."</div>".$arData["IBLOCK_SECTION_ID"];
	}

	// tag control
	$arFields[] = array("id" => "TAGS", "name" => GetMessage("WD_TAGS"), "type" => "custom");
	$aTags = array_filter(explode(',', $arResult["ELEMENT"]["TAGS"]));
	$aTagLinks = array();
	foreach ($aTags as $sTag)
	{
		$aTagLinks[] = "<a href=\"javascript:void(0);\" onclick=\"WDSearchTag('".urlencode(CUtil::JSEscape(trim($sTag)))."')\">".trim(htmlspecialcharsEx($sTag))."</a>";
	}

	$sTagLinks = implode(', ', $aTagLinks);
	if (strlen($sTagLinks) <= 0) $sTagLinks = "&nbsp;";

	$arData["TAGS"] = '<div class="quick-view wd-toggle-edit wd-tags">'. $sTagLinks.'</div>'.'<input type="text" class="quick-edit" style="display:none;" name="TAGS" value="'.htmlspecialcharsbx($arResult["ELEMENT"]["TAGS"]).'" />'; 
	if (IsModuleInstalled("search") && ($arResult["WRITEABLE"] == "Y"))
	{
		ob_start();
		$arTagParams = array(
			"VALUE" => htmlspecialcharsbx($arResult["ELEMENT"]["TAGS"]), //$arResult["ELEMENT"]["~TAGS"],
			"NAME" => "TAGS"
		);
		if ( isset($arParams["OBJECT"]->attributes['group_id']))
		{
			$groupID = intval($arParams["OBJECT"]->attributes['group_id']);
			if ($groupID > 0)
			{
				$arTagParams['arrFILTER'] = 'socialnetwork';
				$arTagParams['arrFILTER_socialnetwork'] = $groupID;
			}
		}
		$APPLICATION->IncludeComponent(
			"bitrix:search.tags.input",
			"",
			$arTagParams,
			null,
			array("HIDE_ICONS" => "Y"));
		$arData["TAGS"] = '<div class="quick-view wd-toggle-edit wd-tags">'.$sTagLinks.'</div>'. ob_get_clean(); 
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
				'content' => trim($elementPreviewText),
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
if (strlen($elementPreviewText) <= 0) $elementPreviewText="&nbsp;";
	$arFields[] = array("id" => "PREVIEW_TEXT", "name" => GetMessage("WD_DESCRIPTION"), "type" => "custom", "value" => 
		"<div class=\"quick-view wd-toggle-edit wd-description\">".$elementPreviewText."</div><div class=\"quick-edit\">".$lhe."</div>"); 

		if ($arParams["USE_WORKFLOW"] == "Y")
		{
			$arFields[] = array("id" => "WF_DELIMITER", "name" => "", "colspan" => "Y", "type" => "custom", "value" => "<div class=\"wd-form-delimiter\">".GetMessage("WD_WF")."</div>");
			if ($arParams["SHOW_WORKFLOW"] != "N")
			{
				$arFields[] = array("id" => "WF_STATUS_ID", "name" => GetMessage("WD_FILE_STATUS"), "type" => "custom"); 

				$arData["WF_STATUS_ID"] = '<select class="quick-edit" name="WF_STATUS_ID">'; 
				foreach ($arResult["WF_STATUSES"] as $key => $val)
					$arData["WF_STATUS_ID"] .= '<option value="'.$key.'"'.($key == $arResult["ELEMENT"]["WF_STATUS_ID"] ? ' selected="selected"' : '').'>'.htmlspecialcharsEx($val).'</option>'; 
				$arData["WF_STATUS_ID"] .= '</select>'; 
				$sCurStatus = htmlspecialcharsbx($arResult["WF_STATUSES"][$arResult["ELEMENT"]["WF_STATUS_ID"]]);
				if (strlen($sCurStatus) < 1) $sCurStatus = '&nbsp;';
				$arData["WF_STATUS_ID"] .= "<div class=\"quick-view wd-toggle-edit wd-wfstatus\">".$sCurStatus."</div>";
			}

			$arFields[] = array("id" => "WF_COMMENTS", "name" => GetMessage("WD_FILE_COMMENTS"), "type" => "custom"); 
			$arData["WF_COMMENTS"] = '<textarea class="quick-edit wd-wfcomments" name="WF_COMMENTS">'.$arResult["ELEMENT"]["WF_COMMENTS"].'</textarea>';
			$arData["WF_COMMENTS"] .= "<div class=\"quick-view wd-toggle-edit wd-wfcomments\">".((strlen($arResult["ELEMENT"]["WF_COMMENTS"]) > 0) ? $arResult["ELEMENT"]["WF_COMMENTS"] : "&nbsp;")."</div>";
		}

	if (isset($arResult['ELEMENT']['USER_FIELDS'])
		&& is_array($arResult['ELEMENT']['USER_FIELDS'])
	)
	{
		foreach ($arResult['ELEMENT']['USER_FIELDS'] as $fieldCode => $arUserField)
		{
			$arUserField["EDIT_FORM_LABEL"] = StrLen($arUserField["EDIT_FORM_LABEL"]) > 0 ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];
			$arUserField["~EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"];
			$arUserField["EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"];

			$fieldView = "";

			ob_start();
			$APPLICATION->IncludeComponent(
				"bitrix:system.field.view", 
				$arUserField["USER_TYPE_ID"], 
				array("arUserField" => $arUserField),
				null,
				array("HIDE_ICONS"=>"Y")
			);
			$fieldView = ob_get_clean() . "&nbsp;";

			$fieldEdit = "<table><tr><td>";
			//$fieldEdit .= $GLOBALS['USER_FIELD_MANAGER']->GetEditFormHTML(false, $GLOBALS[$FIELD_NAME], $arUserField);
			ob_start();
				$APPLICATION->IncludeComponent(
					"bitrix:system.field.edit",
					$arUserField["USER_TYPE_ID"],
					array(
						"bVarsFromForm" => false,
						"arUserField" => $arUserField,
						"form_name" => "wd_upload_form"
					), null, array("HIDE_ICONS" => "Y")
				);
			$fieldEdit .= ob_get_clean() . "&nbsp;";
			$fieldEdit .= "</td></tr></table>";

			$arData[$fieldCode] = '<div class="quick-view wd-toggle-edit wd-ufview">'. $fieldView.'</div>'.'<div class="quick-edit wd-ufedit" style="display:none;">' .$fieldEdit. '</div>';
			$arFields[] = array("id" => $fieldCode, "name" => $arUserField["EDIT_FORM_LABEL"], "type" => "custom"); 
		}
	}

			
		if ($arResult["WRITEABLE"] == "Y")
		{
	$arFields[] = array("id" => "BUTTONS2", "name" => "", "type" => "custom", "colspan" => true, "value" => "
		<table width=\"100%\"><tr>
<td style=\"width:30.5%; background-image:none;\"></td><td style=\"background-image:none;\">
<input type=\"button\" class=\"button-view\" style=\"margin-right:10px; float: left; display: none;\" id=\"wd_end_edit\" value=\"".htmlspecialcharsbx(GetMessage("WD_END_EDIT"))."\" />
<input type=\"button\" class=\"button-view\" style=\"margin-right:10px; float: left; display: none;\" id=\"wd_edit_office\" value=\"".htmlspecialcharsbx(GetMessage("WD_EDIT_OFFICE"))."\" />
<input type=\"button\" class=\"button-edit\" style=\"margin-right:10px; float: left; display: none;\" id=\"wd_commit\" value=\"".htmlspecialcharsbx(GetMessage("WD_SAVE"))."\" /> 
<input type=\"button\" class=\"button-edit\" style=\"margin-right:10px; float: left; display: none;\" id=\"wd_rollback\" value=\"".htmlspecialcharsbx(GetMessage("WD_CANCEL"))."\" /> 
</td></tr></table>");
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
			$nameTemplate = CSite::GetNameFormat(false);
			$dbUser = $USER->GetByID($res["WF_LOCKED_BY"]);
			$arUser = $dbUser->Fetch();
			$res["LOCKED_USER_NAME"] = htmlspecialcharsbx("(".$arUser["LOGIN"].") ");
			$res['LOCKED_USER_NAME'] .= CUser::FormatName($nameTemplate, $arUser);
		}

		$status .= '<div class="element-status-'.$res['LOCK_STATUS'].'">';
		if ($res['LOCK_STATUS'] == "yellow")
			$status .= '['.GetMessage("IBLOCK_YELLOW_MSG",array('#DATE#' => $lockTill)).']';
		else
			$status .= '['.GetMessage("IBLOCK_RED_MSG",array('#NAME#' => $res['LOCKED_USER_NAME'])).']';
		$status .= '</div>';
	}

	$arTabs = array(
		array(
			"id" => (isset($arParams["TAB_ID"]) ? $arParams["TAB_ID"] : "tab_main"),
			"name" => GetMessage("WD_DOCUMENT"),
			"title" => GetMessage("WD_DOCUMENT_ALT"),
			"fields" => $arFields,
		)
	); 

}

if (($arParams["MERGE_VIEW"] == "Y") && ($this->__component->__parent))
{
	$this->__component->__parent->arResult["TABS"][] = $arTabs[0];
	if (empty($this->__component->__parent->arResult["DATA"]))
		$this->__component->__parent->arResult["DATA"] = array();
	$this->__component->__parent->arResult["DATA"] = array_merge($this->__component->__parent->arResult["DATA"], $arData);
} else {
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.form",
		"",
		array(
			"FORM_ID" => $arParams["FORM_ID"],
			"TABS" => $arTabs,
			"BUTTONS" => array(
				"back_url" => $APPLICATION->GetCurPageParam("cancel=Y&edit=Y&".bitrix_sessid_get(), array("cancel", "edit", "result")), 
				"custom_html" => '<input type="hidden" name="ELEMENT_ID" value="'.$arParams["ELEMENT_ID"].'" />'.
					'<input type="hidden" name="edit" value="Y" />'.
					'<input type="hidden" name="ACTION" value="'.$arParams["ACTION"].'" />'.
					implode('', $arCustomFields)
				),
			"DATA" => $arData,
			"SHOW_SETTINGS" => false
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
}
if ($this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = (is_array($this->__component->__parent->arResult["arButtons"]) ?
		$this->__component->__parent->arResult["arButtons"] : array());

	if ($arParams["PERMISSION"] >= "W" )
	{
		if ($arResult["ELEMENT"]["SHOW"]["UNLOCK"] == "Y" || $arResult["ELEMENT"]["SHOW"]["LOCK"] == "Y")
		{
			$this->__component->__parent->arResult["arButtons"]["unlock"] = array(
				"TEXT" => (($arResult["ELEMENT"]["SHOW"]["LOCK"] == "Y")?GetMessage("WD_LOCK"):GetMessage("WD_UNLOCK")),
				"TITLE" => GetMessage("WD_LOCK_TITLE"),
				"LINK" => "javascript:WDToggleLock();",
				"ICON" => "btn-unlock element-unlock");
		}

		if ($arResult["ELEMENT"]["SHOW"]["UNDELETE"] == "Y")
		{
			$this->__component->__parent->arResult["arButtons"]["undelete"] = array(
				"TEXT" => GetMessage("WD_UNDELETE"),
				"TITLE" => GetMessage("WD_UNDELETE_TITLE"),
				"LINK" => WDAddPageParams($arResult["ELEMENT"]["URL"]["UNDELETE"], array("edit"=>"Y", "sessid"=>bitrix_sessid())),
				"ICON" => "btn-unlock element-unlock");
		}
	}

	$this->__component->__parent->arResult["arButtons"]["copy_link"] = array(
		"TEXT" => GetMessage("WD_COPY_LINK"),
		"TITLE" => GetMessage("WD_COPY_LINK_TITLE"),
		"LINK" => "javascript:WDCopyLinkDialog('".($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".CUtil::JSEscape($arResult["ELEMENT"]["URL"]["THIS"]))."')",
		"ICON" => "btn-copy element-copy");


	$arContextSubMenu = array();

	if ($arResult["WRITEABLE"] == "Y")
	{
		if ($arResult["ELEMENT"]["SHOW"]["EDIT"] == "Y")
		{
			$arContextSubMenu["element_rename"] = array(
				"TEXT" => GetMessage("WD_RENAME_NAME"),
				"TITLE" => GetMessage("WD_RENAME_TITLE"),
				"LINK" => "javascript:WDRename()",
				"ICON" => "element_rename");
		}

		if (! ($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"] > 0))
		{
			if($arParams["OBJECT"]->CheckWebRights(array("action" => "create")))
			{
				$arContextSubMenu["element_copy"] = array(
					"TEXT" => GetMessage("WD_COPY_NAME"),
					"TITLE" => GetMessage("WD_COPY_TITLE"),
					"LINK" => "javascript:".$GLOBALS['APPLICATION']->GetPopupLink(
						Array(
							"URL"=> WDAddPageParams(
								$arResult["ELEMENT"]["URL"]["~SECTIONS_DIALOG"], 
								array(
									"ACTION" => "COPY",
									"NAME" => urlencode($arResult["ELEMENT"]["NAME"]),
									"ID" => "E".$arResult["ELEMENT"]["ID"]
								), 
								false),
							"PARAMS" => Array("width" => 450, "height" => 400)
						)
					),
					"ICON" => "element_copy"); 
			}

			if ($arResult["ELEMENT"]["SHOW"]["EDIT"] == "Y")
			{
				$arContextSubMenu["element_move"] = array(
					"TEXT" => GetMessage("WD_MOVE_NAME"),
					"TITLE" => GetMessage("WD_MOVE_TITLE"),
					"LINK" => "javascript:".$GLOBALS['APPLICATION']->GetPopupLink(
										Array(
											"URL"=> WDAddPageParams(
												$arResult["ELEMENT"]["URL"]["~SECTIONS_DIALOG"], 
												array(
													"ACTION" => "MOVE",
													"NAME" => urlencode($arResult["ELEMENT"]["NAME"]),
													"ID" => "E".$arResult["ELEMENT"]["ID"]
												), 
												false),
											"PARAMS" => Array("width" => 450, "height" => 400)
										)
									),
					"ICON" => "element_move"); 
			}
		}
	}

	if ($arParams["ACTION"] != "CLONE" && 
		($arResult["ELEMENT"]["SHOW"]["HISTORY"] == "Y" || $arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y"))
	{
		if ($arResult["ELEMENT"]["SHOW"]["UNDELETE"] == "Y")
		{
?><script>
	BX(function() { oText['message01'] = "<?=CUtil::JSEscape(GetMessage("WD_DESTROY_FILE_CONFIRM"))?>"; });
</script><?
		}
		if ($arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y")
			$arContextSubMenu["delete"] = array(
				"TEXT" => GetMessage("WD_DELETE_FILE"),
				"TITLE" => (intVal($arResult["ELEMENT"]["WF_PARENT_ELEMENT_ID"]) <= 0 ? GetMessage("WD_DELETE_FILE_ALT") : GetMessage("WD_DELETE_FILE_ALT2")),
				"LINK" => "javascript:WDDrop('".CUtil::JSEscape($arResult["ELEMENT"]["URL"]["~DELETE"])."');",
				"ICON" => "element_delete"); 
	}

	if (!empty($arContextSubMenu))
	{
		$subMenu = array();
		foreach($arContextSubMenu as $id => $menuItem)
		{
			$subMenu[] = array(
				"ICONCLASS"=> $menuItem["ICON"],
				"TEXT"=> $menuItem["TEXT"],
				"ONCLICK" => $menuItem["LINK"],
				"MENU" => array_key_exists("MENU", $menuItem) ? $menuItem["MENU"] : false
			);
		}

		$this->__component->__parent->arResult["arButtons"]["context"] = array(
			"TEXT" => GetMessage("WD_OTHER_ACTIONS"),
			"TITLE" => GetMessage("WD_OTHER_ACTIONS_TITLE"),
			"MENU" => $subMenu,
			"ICON" => ""); 
	}
}
$arOfficeExtensions = __wd_get_office_extensions();
?>
<script>
BX( function() {
	BX.viewElementBind(
		'inner_tab_tab_main',
		{showTitle: true},
		{attr: 'data-bx-viewer'}
	);
});
var WDSearchTag = function(tag)
{
	jsUtils.Redirect({},"<?=WDAddPageParams($arResult["ELEMENT"]["URL"]["SECTION"], array("%3FTAGS" => "#tags#"));?>".replace("#tags#", BX.util.urlencode(tag)));
}

var WDRename = function()
{
	bxForm_<?=$arParams["FORM_ID"]?>.SelectTab('tab_main');
	var nameField = BX.findChild(document, {'class':'wd-file-name'}, true);
	WDActivateEdit(nameField);
}

var WDSetHeader = function(result)
{
	var header = BX.findChild(BX('inner_tab_tab_main'), {'class':'bx-form-title'}, true);
	for (var i=header.children.length-1; i >= 0; i--) 
		BX.remove(header.children[i]);
	header.innerHTML += result.status;
}

function WDCopyLinkDialog(url)
{
	var wdc = new BX.CDialog({'title': '<?=CUtil::JSEscape(GetMessage('WD_COPY_LINK_TITLE'));?>', 'content':"<form><input type=\"text\" readonly=\"readonly\" style=\"width:482px\"><br /><p><?=CUtil::JSEscape(GetMessage("WD_COPY_LINK_HELP"));?></p></form>", 'width':520, 'height':120});
	
	wdc.SetButtons("<input type=\"button\" onClick=\"BX.WindowManager.Get().Close()\" value=\"<?=CUtil::JSEscape(GetMessage('MAIN_CLOSE'))?>\">");
	wdc.Show();

	var wdci = BX.findChild(wdc.GetForm(), {'tag':'input'})
	wdci.value = url.replace(/ /g, "%20");
	wdci.select();
}

<? if (strlen($status)>0) { ?>
BX(function() { WDSetHeader({'status': "<?=CUtil::JSEscape($status)?>"}); });
<? } ?>


<? if ($arResult["WRITEABLE"] == "Y") { ?>

var viewElements;
var editElements;
var docID;
var downloadUrl = null;
var editMode = false;
var isRedLocked = false;
var mayUnlock = false;
var downloadDone = false;
var fileDownloadDone = false;
var timeCheck = null;
var timeLeft = null;


var WDChangeMode = function(fields, buttons, elm)
{
	fields = !!fields;
	buttons = !!buttons;
	editMode = fields || buttons;
	var localViewElements = viewElements;
	var localEditElements = editElements;

	if (elm != null)
	{
		localViewElements = BX.findChild(elm, {'class': 'quick-view'}, true, true);
		localEditElements = BX.findChild(elm, {'class': 'quick-edit'}, true, true);
	}

	var on	= (fields ? 'block' : 'none');
	var off = (fields ? 'none' : 'block');
	if (isRedLocked) 
		on = off = isRedLocked;
	

	for (var i in localViewElements)
		localViewElements[i].style.display = off;

	for (var i in localEditElements)
		localEditElements[i].style.display = on;

	var on	= (buttons ? 'block' : 'none');
	var off = (buttons ? 'none' : 'block');
	if (isRedLocked) 
		on = off = isRedLocked;

	for (var i in viewButtons)
		viewButtons[i].style.display = off;

	for (var i in editButtons)
		editButtons[i].style.display = on;
}

var WDCommit = function()
{
	BX('wd_commit').disabled = true;
	BX('wd_rollback').disabled = true; 
	editMode = false;
	var formParent = BX('tab_main_edit_table').parentNode;
	var obForm = document.createElement('form');
	var obTable = BX('tab_main_edit_table');
	obForm.setAttribute('enctype', 'multipart/form-data');
	obForm.setAttribute('method', 'POST');
	obForm.setAttribute('id', 'tab_main_form');
	obForm.setAttribute('action', "<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>");
	formParent.appendChild(obForm);
	obForm.appendChild(obTable);
	obForm.submit();
}

var WDRollback = function()
{
	BX('wd_commit').disabled = true;
	BX('wd_rollback').disabled = true; // ie9 hides the button itself if we remove timeout !
	setTimeout(function() {
		window.location.reload(true);
	}, 100); 
}

var WDEditDocument = function(url)
{
	WDChangeMode(false, false);
	BX('wd_end_edit').disabled = false;
	downloadDone = false;
	if (editMode && fileDownloadDone == false)
		window.location.href=downloadUrl;
	downloadUrl = url;
}

var WDEditLockReally = function(result)
{
	if (typeof(result) == 'undefined')
		return window.location.reload();
	WDSetHeader(result);
	isRedLocked = (result.result == 'red');
	WDChangeMode(false, false);
	BX('wd_end_edit').disabled = false;
	if ((result.result == 'yellow') && fileDownloadDone == false)
		window.location.href=downloadUrl;
}

var WDToggleLock = function()
{
	var label = BX.findChild(document, {'class': 'btn-unlock'}, true);
	var tableHeader = BX.findChild(document, {'class': 'bx-edit-tab-title'}, true);
	
	var locked = ( BX.findChild(tableHeader, {'class': 'element-status-yellow'}, true) != null );

	if (locked || (isRedLocked && mayUnlock))
	{
		if (label && label.innerHTML.length > 0)
		{
			label.innerHTML = '<?=CUtil::JSEscape(GetMessage("WD_LOCK"))?>';
		} else {
			label.nextSibling.innerHTML = '<?=CUtil::JSEscape(GetMessage("WD_LOCK"))?>';
		}
		WDLockAction('UNLOCK', WDEditLockReally);
	} else {
		if (label && label.innerHTML.length > 0) 
		{
			label.innerHTML = '<?=CUtil::JSEscape(GetMessage("WD_UNLOCK"))?>';
		} else {
			label.nextSibling.innerHTML = '<?=CUtil::JSEscape(GetMessage("WD_UNLOCK"))?>';
		}

		fileDownloadDone = true;
		WDLockAction('LOCK', WDEditLockReally);
	}
}

var WDFileUpload = function()
{
	fileDownloadDone = false;
	if (!editMode)
	{
		fileDownloadDone = true;
	}

	var wait = BX.showWait();
	var uploadDialog = null;
	BX.ajax.get("<?=CUtil::JSEscape(WDAddPageParams($arResult["ELEMENT"]["URL"]["UPLOAD"], array("use_light_view" => "Y", "close_after_upload" => "Y", "update_document" => $arParams["ELEMENT_ID"]), false))?>", null, function(data) {
		BX.closeWait(null, wait);
		uploadDialog = new BX.CDialog({"content": data || '&nbsp', "width":650 , "height":150 });
		// disable window events
		uploadDialog.WDUploaded = false;
		uploadDialog.WDUpdate = true;
		editMode = false;
		// reenable if required
		BX.addCustomEvent(uploadDialog, 'onBeforeWindowClose', function() {
			if (!(uploadDialog.WDUploaded))
			{
				editMode = true;
			}
		});
		//show dialog
		uploadDialog.Show();
	});
}

var WDLockAction = function(action, callback)
{
	var params = "ELEMENT_ID="+docID+"&ACTION="+action+"&AJAX_CALL=Y&edit=Y&sessid=<?=bitrix_sessid()?>";
	var url = "<?=CUtil::JSEscape(str_replace("#edit", "", POST_FORM_ACTION_URI))?>";
	BX.ajax.loadJSON(url+(url.indexOf('?')>0 ? '&' : '?')+params, callback); 
}

var WDEnterSubmit = function(e)
{
	var ev = e || window.event;
	var key = ev.keyCode;

	if (key == 13)
	{
		BX('wd_commit').click();
	}
	else if (key == 27)
	{
		BX('wd_rollback').click();
	}
}


function WDActivateEdit(elm)
{
	editMode = true;
	WDChangeMode(true, true, elm.parentNode);
	inputField = BX.findChild(elm.parentNode, {'tag': 'input'}, true);
	if (! inputField) inputField = BX.findChild(elm.parentNode, {'tag': 'textarea'}, true);
	if (! inputField) inputField = BX.findChild(elm.parentNode, {'tag': 'select'}, true);
	if (inputField)
	{
		try {
			inputField.focus();
		} catch (e) {}
	}
}

function WDActivateQuickEdit(elm)
{
	var aHrefs = BX.findChild(elm, {'tag': 'a'}, true, true);
	for (var j in aHrefs)
	{
		if(BX.hasClass(aHrefs[j], 'element-title'))
		{
			continue;
		}
		BX.bind(aHrefs[j], 'click', function(e) {
			if (!e) var e = window.event;
			if (e.stopPropagation)
				e.stopPropagation();
			else
				e.cancelBubble = true;
		});
	}
	BX.bind(elm, 'mouseover', function() { BX.addClass(elm, 'wd-input-hover'); });
	BX.bind(elm, 'mouseout',  function() { BX.removeClass(elm, 'wd-input-hover'); });
	BX.bind(elm, 'click',	  function(e) {
		var eTarget = e.target || e.srcElement;
		if(eTarget && eTarget.tagName.toLowerCase() != 'a' )
		{
			WDActivateEdit(elm);
		}
	});
}

BX(function() {
	if (window.location.href.indexOf("#postform") > -1) // comment preview
	{
		bxForm_<?=$arParams["FORM_ID"]?>.SelectTab('tab_comments');
		var aToggler = BX('forumCollapseToggler');
		if (aToggler)
			toggleReviewForm(aToggler);
	}

	var viewRoot = BX('tab_main_edit_table');
	docID = BX.findChild(viewRoot, {'attribute':{'name':'ELEMENT_ID'}}, true).value;
	BX.addClass(BX.findChild(viewRoot, {'class': 'search-tags'}, true), 'quick-edit');
	viewElements = BX.findChild(viewRoot, {'class': 'quick-view'}, true, true);
	editElements = BX.findChild(viewRoot, {'class': 'quick-edit'}, true, true);
	viewButtons = BX.findChild(viewRoot, {'class': 'button-view'}, true, true);
	editButtons = BX.findChild(viewRoot, {'class': 'button-edit'}, true, true);
	BX.bind(BX('wd_end_edit'), 'click', function() { WDFileUpload()});
	BX.bind(BX('wd_rollback'), 'click', WDRollback);
	BX.bind(BX('wd_commit'), 'click', WDCommit);

	BX.bind(BX.findChild(viewRoot, {'tag':'input', 'class':'wd-file-name'}, true), 'keypress', WDEnterSubmit);
	BX.bind(BX.findChild(viewRoot, {'tag':'input', 'property':{'name':'TAGS'}}, true), 'keypress', WDEnterSubmit);

	// hover hilight and edit
	var aElements = BX.findChild(viewRoot, {'class':'wd-toggle-edit'}, true, true);
	for (var i in aElements)
	{
		if (! BX.hasClass(aElements[i], 'no-quickedit'))
		{
			WDActivateQuickEdit(aElements[i]);
		}
	}

	if (BX.findChild(viewRoot, {'class': 'element-status-yellow'}, true))
	{
		downloadDone = true;
		editMode = true;
		mayUnlock = true;
	} else {
		editMode = false;
	}

	if (BX.findChild(viewRoot, {'class': 'element-status-red'}, true))
	{
		isRedLocked = true;
<?
	if ($arParams["PERMISSION"] > "W")
	{
?>
		mayUnlock = true;
<?
	}
?>
	} else {
		isRedLocked = false;
	}

<? // fix UF edit template ?>

	var arDivEdit = BX.findChildren(BX('tab_main_edit_table'), {'className': 'wd-ufedit'}, true);
	if (!! arDivEdit
		&& arDivEdit.length > 0)
	{
		for (i in arDivEdit)
		{
			var ufDiv = arDivEdit[i];

			if (
				(ufDiv.children.length > 0)
				&& (ufDiv.children[0].tagName == 'TABLE')
				&& (!! ufDiv.children[0].rows)
				&& (ufDiv.children[0].rows.length == 1)
				&& (!! ufDiv.children[0].rows[0].cells)
				&& (ufDiv.children[0].rows[0].cells.length == 2)
			)
			{
				BX.style(ufDiv.children[0].rows[0].cells[0], 'display', 'none'); // hide UF label
			}
		}
	}

	WDChangeMode(false, false);
	if (window.location.href.indexOf("#upload") > -1)
	{
		WDFileUpload();
	}
	else if (window.location.href.indexOf('#edit') > -1)
	{
		WDEditDocument("<?=CUtil::JSEscape($arResult["ELEMENT"]["URL"]["DOWNLOAD"])?>");
	}

	var btn_edit_office = BX('wd_edit_office');

<? if (in_array($arResult["ELEMENT"]["EXTENTION"], $arOfficeExtensions) && $arResult['ELEMENT']['bShowWebDav']) { ?>
	var moffice = WDCheckOfficeEdit();
	if (moffice)
	{
		var officetitle = WDEditOfficeTitle();
		btn_edit_office.style.display = 'block';
		if (officetitle != false)
			btn_edit_office.value = officetitle;
		BX.bind(btn_edit_office, 'click', function() {
			EditDocWithProgID('<?=CUtil::JSEscape($arResult["ELEMENT"]["URL"]["~THIS"])?>')
		});
	} else {
		BX.remove(btn_edit_office);
	}
<? } else { ?>
	BX.remove(btn_edit_office);
<? } ?>
});
<? } ?>
</script>
