<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Types\EnumType;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (isset($arResult['CLOSE_SLIDER']) && $arResult['CLOSE_SLIDER'] === true)
{
	?>
	<script>
		BX.ready(
			function()
			{
				if (top.BX.SidePanel.Instance.getTopSlider())
				{
					top.BX.SidePanel.Instance.postMessage(
						window,
						"Crm.Config.Fields.Edit:onChange",
						[]
					);
					BX.addCustomEvent(
						top.BX.SidePanel.Instance.getTopSlider().getWindow(),
						"SidePanel.Slider:onCloseComplete",
						function (event) {
							top.BX.SidePanel.Instance.destroy(event.getSlider().getUrl());
						}
					);
				}
				top.BX.SidePanel.Instance.close(false);
			}
		);
	</script>
	<?php
	return;
}

CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

$arToolbarButtons = Array();
$arToolbarButtons[] = array(
	"TEXT"=>GetMessage("CRM_FE_TOOLBAR_FIELDS"),
	"TITLE"=>GetMessage("CRM_FE_TOOLBAR_FIELDS_TITLE"),
	"LINK"=>$arResult["FIELDS_LIST_URL"],
	"ICON"=>"btn-view-fields",
);
if (!$arResult["NEW_FIELD"])
{
	$arToolbarButtons[] = array(
		"TEXT"=>GetMessage("CRM_FE_TOOLBAR_ADD"),
		"TITLE"=>GetMessage("CRM_FE_TOOLBAR_ADD_TITLE"),
		"LINK"=>$arResult["FIELD_ADD_URL"],
		"ICON"=>"btn-add-field",
	);
	$arToolbarButtons[] = array(
		"SEPARATOR"=>"Y",
	);
	$arToolbarButtons[] = array(
		"TEXT"=>GetMessage("CRM_FE_TOOLBAR_DELETE"),
		"TITLE"=>GetMessage("CRM_FE_TOOLBAR_DELETE_TITLE"),
		"LINK"=>"javascript:jsDelete('".CUtil::JSEscape("form_".$arResult["FORM_ID"])."', '".GetMessage("CRM_FE_TOOLBAR_DELETE_WARNING")."')",
		"ICON"=>"btn-delete-field",
	);
	$arToolbarButtons[] = array(
		"SEPARATOR"=>"Y",
	);
}


$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS"=> $arToolbarButtons
	),
	$component, array("HIDE_ICONS" => "Y")
);

$arTab1Fields = array(
	array("id"=>"SORT", "name"=>GetMessage("CRM_FE_FIELD_SORT"), "params"=>array("size"=>5))
);

$arTab1Fields[] = array(
	"id"=>"USE_MULTI_LANG_LABEL",
	"name"=> GetMessage("CRM_FE_FIELD_USE_MULTI_LANG_LABEL"),
	"type"=>"checkbox",
	"value" => $arResult['USE_MULTI_LANG_LABEL'] ? "Y" : "N"
);

$arTab1Fields[] = array(
	"id" => "COMMON_EDIT_FORM_LABEL",
	"name" => GetMessage("CRM_FE_FIELD_COMMON_LABEL"),
	"required" => true
);

$arLabelInputNames = array();
foreach($arResult['LANGUAGES'] as $lid => $arLang)
{
	$langName = !empty($arLang['NAME']) ? $arLang['NAME'] : $lid;
	$inputName = "EDIT_FORM_LABEL[{$lid}]";
	$arTab1Fields[] = array(
		"id" => $inputName,
		"name" => GetMessage("CRM_FE_FIELD_NAME")." ({$langName})",
		"required" => true
	);

	$arLabelInputNames[] = $inputName;
}

if (!$arResult["DISABLE_MANDATORY"])
{
	$arTab1Fields[] = [
		"id"=>"MANDATORY",
		"name"=>GetMessage("CRM_FE_FIELD_IS_REQUIRED"),
		"type"=>"checkbox",
	];
}

if($arResult["NEW_FIELD"] && !$arResult["DISABLE_MULTIPLE"])
	$arTab1Fields[] = array(
		"id"=>"MULTIPLE",
		"name"=>GetMessage("CRM_FE_FIELD_MULTIPLE"),
		"type"=>"checkbox",
	);
else
	$arTab1Fields[] = array(
		"id"=>"MULTIPLE",
		"name"=>GetMessage("CRM_FE_FIELD_MULTIPLE"),
		"type"=>"label",
		"value"=> $arResult["FIELD"]['MULTIPLE'] == 'Y'? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"),
	);

$showInFilterParams = array();
if(isset($arResult['ENABLE_SHOW_FILTER']) && !$arResult['ENABLE_SHOW_FILTER'])
{
	$showInFilterParams["disabled"] = "disabled";
}

$arTab1Fields[] = array(
	"id" => "SHOW_FILTER",
	"name" => GetMessage("CRM_FE_FIELD_SHOW_FILTER"),
	"type" => "checkbox",
	"value" => ($arResult['FIELD']['SHOW_FILTER'] ?? null) !== 'N',
	"params" => $showInFilterParams
);

$arTab1Fields[] = array(
	"id"=>"SHOW_IN_LIST",
	"name"=>GetMessage("CRM_FE_FIELD_SHOW_IN_LIST"),
	"type"=>"checkbox",
	"value"=>$arResult['FIELD']['SHOW_IN_LIST'] === 'Y'
);

if($arResult["NEW_FIELD"])
	$arTab1Fields[] = array(
		"id"=>"USER_TYPE_ID",
		"name"=>GetMessage("CRM_FE_FIELD_TYPE"),
		"type"=>"list",
		"items"=>$arResult["TYPES"],
		"params"=>array(
			'OnChange' => 'jsTypeChanged(\'form_'.$arResult["FORM_ID"].'\', this);',
		),
	);
else
	$arTab1Fields[] = array(
		"id"=>"USER_TYPE_ID",
		"name"=>GetMessage("CRM_FE_FIELD_TYPE"),
		"type"=>"label",
		"value"=> $arResult["TYPES"][$arResult["FIELD"]['USER_TYPE_ID']],
	);

$arAdditionalFields = $arResult["FIELD"]["ADDITIONAL_FIELDS"];
foreach($arAdditionalFields as $ar)
	$arTab1Fields[] = $ar;

$arTabs = array(
	array("id"=>"tab1", "name"=>GetMessage("CRM_FE_TAB_EDIT"), "title"=>GetMessage("CRM_FE_TAB_EDIT_TITLE"), "icon"=>"", "fields"=>$arTab1Fields),
);

$custom_html = "";
if(isset($arResult["LIST"]) && is_array($arResult["LIST"]))
{
	if($arResult['FORM_DATA']['USER_TYPE_ID'] === 'enumeration')
	{
		$sort = 10;
		$html = '<table id="tblLIST" width="100%">
			<tr>
			<td align="center" width="1%"></td>
			<td align="center" width="1%"></td>
			<td width="97%"></td>
			<td align="center" width="1%"></td>
			</tr>
		';
		foreach($arResult["LIST"] as $arEnum)
		{
			$value = $arEnum['VALUE'] ?? null;
			$html .= '
				<tr>
				<td align="center"><div class="sort-arrow sort-up" onclick="sort_up(this);" title="'.GetMessage("CRM_FE_SORT_UP_TITLE").'"></div></td>
				<td align="center"><div class="sort-arrow sort-down" onclick="sort_down(this);" title="'.GetMessage("CRM_FE_SORT_DOWN_TITLE").'"></div></td>
				<td>
					<input type="hidden" name="LIST['.$arEnum["ID"].'][SORT]" value="'.$sort.'" class="sort-input">
					<input type="text" size="35" name="LIST['.$arEnum["ID"].'][VALUE]" value="'. $value .'" class="value-input">
				</td>
				<td align="center"><div class="delete-action" onclick="delete_item(this);" title="'.GetMessage("CRM_FE_DELETE_TITLE").'"></div></td>
				</tr>
			';
			$sort += 10;
		}

		$html .= '</table>';
		$html .= '<input type="button" value="'.GetMessage("CRM_FE_LIST_ITEM_ADD").'" onClick="addNewTableRow(\'tblLIST\')">';

		$listTextValues = ($arResult['FORM_DATA']['LIST_TEXT_VALUES'] ?? '');
		$displayStyle = empty($listTextValues) ? 'display:none; ' : '';
		$blockStyles = 'style="' . $displayStyle . 'width:100%"';

		$html .= '
			<br><br>
			<a class="href-action" href="javascript:void(0)" onclick="toggle_input(\'import\'); return false;">'.GetMessage("CRM_FE_ENUM_IMPORT").'</a>
			<div id="import" ' . $displayStyle . '>
				<p>'.GetMessage("CRM_FE_ENUM_IMPORT_HINT").'</p>
				<textarea name="LIST_TEXT_VALUES" id="LIST_TEXT_VALUES" style="width:100%" rows="20">' . $listTextValues . '</textarea>
			</div>
		';

		$html .= '
			<br><br>
			<a class="href-action" href="javascript:void(0)" onclick="toggle_input(\'defaults\'); return false;">'.($arResult["FORM_DATA"]["MULTIPLE"] == "Y"? GetMessage("CRM_FE_ENUM_DEFAULTS"): GetMessage("CRM_FE_ENUM_DEFAULT")).'</a>
			<div id="defaults" ' . $blockStyles . '>
			<br>
		';

		if($arResult["FORM_DATA"]["MULTIPLE"] == "Y")
			$html .= '<select multiple name="LIST_DEF[]" id="LIST_DEF" size="10">';
		else
			$html .= '<select name="LIST_DEF[]" id="LIST_DEF" size="1">';

		if (($arResult['FORM_DATA']['IS_REQIRED'] ?? 'N') !== 'Y')
		{
			$html .= '<option value=""' . (count($arResult["LIST_DEF"]) == 0 ? ' selected' : '') . '>' . Loc::getMessage('CRM_FE_ENUM_NO_DEFAULT') . '</option>';
		}

		foreach ($arResult['LIST'] as $arEnum)
		{
			$selected = (isset($arResult['LIST_DEF'][$arEnum['ID']]) ? ' selected' : '');
			$value = $arEnum['VALUE'] ?? null;
			if ($value === null)
			{
				continue;
			}
			$html .= '<option value="' . $arEnum["ID"] . '"' . $selected . '>' . $value . '</option>';
		}

		$html .= '
				</select>
			</div>
		';

		$arTabs[] = array(
			"id"=>"tab2",
			"name"=>GetMessage("CRM_FE_TAB_LIST"),
			"title"=>GetMessage("CRM_FE_TAB_LIST_TITLE"),
			"icon"=>"",
			"fields"=>array(
				array(
					"id" => "LIST",
					"colspan" => true,
					"type" => "custom",
					"value" => $html,
				),
			),
		);
	}
	else
	{
		foreach($arResult["LIST"] as $arEnum)
		{
			$custom_html .= '<input type="hidden" name="LIST['.$arEnum["ID"].'][SORT]" value="'.$arEnum["SORT"].'">'
				.'<input type="hidden" name="LIST['.$arEnum["ID"].'][VALUE]" value="'.$arEnum["VALUE"].'">';
		}
	}
}

$custom_html .= '<input type="hidden" name="action" id="action" value="">';
if(!$arResult["NEW_FIELD"])
	$custom_html .= '<input type="hidden" name="USER_TYPE_ID" id="action" value="'.$arResult["FIELD"]['USER_TYPE_ID'].'">';

?><div class="bx-crm-field-edit-wrapper"><?
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID"=>$arResult["FORM_ID"],
		"TABS"=>$arTabs,
		"BUTTONS"=>array(
			"back_url"=>$arResult["~FIELDS_LIST_URL"],
			"custom_html"=>$custom_html
		),
		"DATA"=>$arResult["FORM_DATA"],
		"SHOW_SETTINGS"=>"N",
		"THEME_GRID_ID"=>$arResult["GRID_ID"],
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>
</div>

<script>
	BX.ready(
		function()
		{
			var form = BX('form_field_edit');
			<?if (isset($arResult['RESTRICTION_CALLBACK']) && $arResult['RESTRICTION_CALLBACK'] !== ''):?>
				form.onsubmit = function()
				{
					<?=$arResult['RESTRICTION_CALLBACK']?>;
					return false;
				};
			<?endif?>
			var commonLabelInput = BX.findChild(form, { "tagName":"INPUT", "attr":{ "name":"COMMON_EDIT_FORM_LABEL" }  }, true, false);
			var commonLabelContainer = BX.findParent(commonLabelInput, { "tagName":"TR"});

			var labelContainers = [];
			var labelInputNames = <?= CUtil::PhpToJSObject($arLabelInputNames)?>;
			for(var i = 0; i < labelInputNames.length; i++)
			{
				var labelInput = BX.findChild(form, { "tagName":"INPUT", "attr":{ "name":labelInputNames[i] } }, true, false);
				var labelContainer = BX.findParent(labelInput, { "tagName":"TR"});
				if(labelContainer)
				{
					labelContainers.push(labelContainer);
				}
			}

			var useLangLabelChbx = BX.findChild(form, { "tagName":"INPUT", "attr":{ "type":"checkbox", "name":"USE_MULTI_LANG_LABEL" }  }, true, false);
			BX.bind(
				useLangLabelChbx,
				"change",
				function()
				{
					layoutLabels(useLangLabelChbx.checked);
				}
			);

			function layoutLabels(useLangLabel)
			{
				if(commonLabelContainer)
				{
					commonLabelContainer.style.display = !useLangLabel ? "" : "none";
					for(var j = 0; j < labelContainers.length; j++)
					{
						labelContainers[j].style.display = useLangLabel ? "" : "none";
					}
				}
			}

			layoutLabels(<?=$arResult['USE_MULTI_LANG_LABEL'] ? 'true' : 'false'?>);
		}
	);
</script>
<?if($arResult["FORM_DATA"]["USER_TYPE_ID"] === 'enumeration'):
	$eDisplay = ($arResult['FIELD']['E_DISPLAY'] ?? null);
	$displayAvailablesShowList = [
		EnumType::DISPLAY_CHECKBOX,
	];
	if (defined('\Bitrix\Main\UserField\Types\EnumType::DISPLAY_DIALOG'))
	{
		$displayAvailablesShowList[] = EnumType::DISPLAY_DIALOG;
	}

	$isDisplayAvailableShowListHeight = !in_array($eDisplay, $displayAvailablesShowList, true);
	$display = ($isDisplayAvailableShowListHeight ? 'true' : 'false');
?><script>
	BX.ready(
			function()
			{
				display_list_length(<?= $display ?>);
				var displayAvailablesShowList = <?= \CUtil::PhpToJSObject($displayAvailablesShowList) ?>;
				var s = BX.findChild(document.body, { "tagName":"SELECT", "attr":{ "name":"E_DISPLAY" }  }, true, false);
				if(s)
				{
					BX.bind(
							s,
							"change",
							function()
							{
								display_list_length(
									(displayAvailablesShowList.indexOf(s.value) === -1)
								);
							}
					);
				}
			}
	);
</script><?
endif;
if(SITE_TEMPLATE_ID === 'bitrix24'):
?><script>
	BX.ready(
			function()
			{
				BX.CrmInterfaceFormUtil.disableThemeSelection("<?= CUtil::JSEscape($arResult["FORM_ID"])?>");
			}
	);
</script><?
endif;?>
