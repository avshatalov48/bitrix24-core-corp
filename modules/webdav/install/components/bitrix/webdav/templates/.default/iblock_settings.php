<?
#require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/admin_lib.php");

$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/iblock_settings.php")));
__IncludeLang($file);
$iblock_id = intval($_REQUEST["IBLOCK_ID"]);

$popupWindow = new CJSPopup('', '');
if (!(CModule::IncludeModule("iblock")))
	return false;
elseif (!(CModule::IncludeModule("webdav")))
	return false;
elseif ($iblock_id <= 0)
	$popupWindow->ShowError(GetMessage("WD_IBLOCK_ID_EMPTY"));

$ob = new CWebDavIblock($iblock_id, '/');

if ($ob->e_rights)
	$permission = $ob->GetPermission('IBLOCK', $iblock_id);
else
	$permission = CIBlock::GetPermission($iblock_id);
$arIBlock = CIBlock::GetArrayByID($iblock_id);

if (($ob->CheckRight($permission, 'iblock_rights_edit') < "X") && (!$GLOBALS['USER']->CanDoOperation('webdav_change_settings')))
	$popupWindow->ShowError(GetMessage("WD_ACCESS_DENIED"));

$bWorkflow = CModule::IncludeModule("workflow");
$bBizproc = CModule::IncludeModule("bizproc");

/********************************************************************
				Actions
********************************************************************/
//$GLOBALS["APPLICATION"]->SetFileAccessPermission($_REQUEST["library_FOLDER"], $_REQUEST["library_FOLDER_PERMISSION"]);
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	CUtil::JSPostUnescape();
	$ib = new CIBlock();
	if (!check_bitrix_sessid())
	{
		$strWarning = GetMessage("WD_ERROR_BAD_SESSID");
	}
	else
	{
		$bSetERights = (isset($_REQUEST['IB_E_RIGHTS']) && ($_REQUEST['IB_E_RIGHTS']=='Y'));
		if ($arIBlock['RIGHTS_MODE'] !== ($bSetERights ? 'E' : 'S'))
		{
			$arFields = array();
			$arFields['RIGHTS_MODE'] = ($bSetERights ? 'E' : 'S');
			if ($bSetERights)
				$arFields['GROUP_ID'] = CIBlock::GetGroupPermissions($iblock_id);
			$res = $ib->Update($iblock_id, $arFields);
			$ob->e_rights = $bSetERights;
		}
		else
		{
			$arFields = array();
			if (isset($_REQUEST['WF_TYPE']))
			{
				$arFields = Array(
					"WORKFLOW" => ($_REQUEST["WF_TYPE"] == "WF"? "Y": "N"),
					"BIZPROC" => ($_REQUEST["WF_TYPE"] == "BP"? "Y": "N")
				);
			}
			else
			{
				if (isset($_REQUEST['WF_TYPE_WF']) && ($_REQUEST['WF_TYPE_WF']=='Y'))
				{
					$arFields["WORKFLOW"] = "Y";
					$arFields["BIZPROC"] = "N";
				}
				elseif (isset($_REQUEST['WF_TYPE_BP']) && ($_REQUEST['WF_TYPE_BP'] == "Y"))
				{
					$arFields["BIZPROC"] = "Y";
					$arFields["WORKFLOW"] = "N";
				}
				else
				{
					$arFields["BIZPROC"] = "N";
					$arFields["WORKFLOW"] = "N";
				}
			}

			$res = $ib->Update($iblock_id, $arFields);

			if ($ob->e_rights)
			{
				$arParams['ENTITY_TYPE'] = 'IBLOCK';
				$arParams['ENTITY_ID'] = $arParams['IBLOCK_ID'] = $iblock_id;
				$arParams['ACTION'] = 'set_rights';
				$arParams['DO_NOT_REDIRECT'] = true;

				include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav.iblock.rights/action.php");
			}
			else
			{
				if (is_array($_REQUEST["GROUP_ADD"]) && !empty($_REQUEST["GROUP_ADD"]))
				{
					foreach ($_REQUEST["GROUP_ADD"] as $key => $group_id)
					{
						$_REQUEST["GROUP"][$group_id] = $_REQUEST["GROUP_ADD_PERMISSION"][$key];
					}
				}
				CIBlock::SetPermission($iblock_id, $_REQUEST["GROUP"]);
				WDClearComponentCache(array(
					"webdav.element.edit",
					"webdav.element.hist",
					"webdav.element.upload",
					"webdav.element.view",
					"webdav.menu",
					"webdav.section.edit",
					"webdav.section.list"));
			}

			$popupWindow->Close($bReload = true, $_REQUEST["back_url"]);
			die();
		}
	}
}
/********************************************************************
				/Actions
********************************************************************/
//HTML output
$popupWindow->ShowTitlebar($arIBlock["NAME"]);
if (isset($strWarning) && $strWarning != "")
	$popupWindow->ShowValidationError($strWarning);

$popupWindow->StartContent();
?>
<div class="webdav_iblock_settings_loader"></div>
<div class="webdav_iblock_settings">
<?
	$arIBlockForm = $arIBlock; 
	if ($bVarsFromForm)
	{
		foreach ($arIBlockForm as $key => $val)
		{
			if (array_key_exists($key, $_REQUEST))
				$arIBlockForm[$key] = $_REQUEST[$key]; 
		}
		$arIBlockForm["WORKFLOW"] = ($_REQUEST["WF_TYPE"] == "WF" ? "Y" : "N");
		$arIBlockForm["BIZPROC"] = ($_REQUEST["WF_TYPE"] == "BP" ? "Y" : "N");
		if (isset($_REQUEST['WF_TYPE_WF']))
		{
			$arIBlockForm["WORKFLOW"] = "Y";
			$arIBlockForm["BIZPROC"] = "N";
		}
		elseif (isset($_REQUEST['WF_TYPE_BP']))
		{
			$arIBlockForm["BIZPROC"] = "Y";
			$arIBlockForm["WORKFLOW"] = "N";
		}
	}
?>
<?=bitrix_sessid_post()?>
<input type="hidden" name="Update" value="Y" />
<input type="hidden" name="IBLOCK_ID" value="<?=$iblock_id?>" />
<?if (!empty($_REQUEST["back_url"])): ?>
<input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($_REQUEST["back_url"])?>" />
<?endif;
?>
<table cellpadding="0" cellspacing="0" border="0" class="edit-table" id="edit2_edit_table" width="100%">
<?
	if ($bWorkflow || $bBizproc)
	{
?>
	<tr class="section">
		<td colspan="2" align="center"><b><?=GetMessage("WD_TAB1_TITLE")?></b></td>
	</tr>
<?

		if ($bWorkflow && $bBizproc):
?>
	<tr>
		<td width="50%" align="right"><?=GetMessage("IB_E_WF_TYPE")?>:</td>
		<td width="50%">
			<select name="WF_TYPE">
				<option value="N"><?=GetMessage("IB_E_WF_TYPE_NONE")?></option>
				<option value="WF" <?=($arIBlockForm["WORKFLOW"] == "Y" ? 'selected="selected"' : "")?>><?=GetMessage("IB_E_WF_TYPE_WORKFLOW")?></option>
				<option value="BP" <?=($arIBlockForm["BIZPROC"] == "Y" ? 'selected="selected"' : "")?>><?echo GetMessage("IB_E_WF_TYPE_BIZPROC")?></option>
			</select>
		</td>
	</tr>
<?
		elseif ($bWorkflow && !$bBizproc):
?>
	<tr>
		<td width="50%" align="right"><label for="WF_TYPE"><?=GetMessage("IB_E_WORKFLOW")?></label></td>
		<td width="50%">
			<input type="checkbox" id="WF_TYPE" name="WF_TYPE_WF" value="WF" <?=($arIBlockForm["WORKFLOW"] == "Y" ? 'checked="checked"' : "")?> />
		</td>
	</tr>
<?
		elseif ($bBizproc && !$bWorkflow):
?>
	<tr>
		<td width="50%" align="right"><label for="WF_TYPE"><?=GetMessage("IB_E_BIZPROC")?></label></td>
		<td width="50%">
			<input type="checkbox" id="WF_TYPE" name="WF_TYPE_BP" value="BP" <?=($arIBlockForm["BIZPROC"] == "Y" ? 'checked="checked"' : "")?> />
		</td>
	</tr>
<?
		endif; 
	}
?>
<? if ($USER->IsAdmin()) { ?>
	<tr class="section">
		<td colspan="2" align="center"><b><?=GetMessage("WD_TAB15_TITLE")?></b></td>
	</tr>
<?
	$UF_ENTITY = $ob->GetUfEntity();
	$arUserField = $ob->GetUfFields();

	$backUrl = '/';
	if (isset($_REQUEST['back_url']))
		$backUrl = $_REQUEST['back_url'];

	foreach ($arUserField as $fieldCode => $field)
	{
		$name = $fieldCode;
		if (!empty($field['EDIT_FORM_LABEL']))
			$name = $field['EDIT_FORM_LABEL'];
		$type = '';
		if (!empty($field['USER_TYPE']['DESCRIPTION']))
			$type = $field['USER_TYPE']['DESCRIPTION'];

?>
	<tr>
		<td width="50%" align="right" valign="top">
			<a href="/bitrix/admin/userfield_edit.php?ID=<?=$field['ID']?>&back_url=<?=htmlspecialcharsbx($backUrl)?>"><?=htmlspecialcharsbx($name)?></a>:
		</td>
		<td width="50%">
			<i><?=htmlspecialcharsbx($type);?></i>
		</td>
	</tr>
<?
	}
?>
	<tr>
		<td colspan="2" align="center" valign="top">
			<a href="/bitrix/admin/userfield_edit.php?ENTITY_ID=<?=htmlspecialcharsbx($UF_ENTITY)?>&back_url=<?=htmlspecialcharsbx($backUrl)?>"><?=GetMessage("IB_WDUF_ADD")?></a>
		</td>
	</tr>
<? } ?>
	<tr class="section">
		<td colspan="2" align="center"><b><?=GetMessage("WD_TAB2_TITLE")?></b></td>
	</tr>
	<tr>
		<td width="50%" align="right" valign="top"><?=GetMessage("IB_E_RIGHTS")?></td>
		<td width="50%">
			<input type="checkbox" id="IB_E_RIGHTS" name="IB_E_RIGHTS" value="Y" <?=($ob->e_rights ? 'checked="checked"' : "")?> /><br />
			<?if ($ob->e_rights) {?>
				<div style="background-color: rgb(253, 255, 201); border: 1px solid rgb(237, 239, 185); font-size: 0.95em; padding:5px 15px; margin: 10px 0 0;"> <?=GetMessage("IB_E_PERMISSIONS_WARN");?> </div>
			<?}?>
		</td>
	</tr>
<?
	if ($ob->e_rights)
	{
		$APPLICATION->IncludeComponent("bitrix:webdav.iblock.rights", ".default", Array(
				"IBLOCK_ID"		=> $iblock_id,
				"ENTITY_TYPE"	=> "IBLOCK",
				"ENTITY_ID"		=> $iblock_id,
				"PERMISSION"	=> $permission, 
				"TAB_ID"		=> 'tab_permissions',
				"SET_TITLE"	=>	"N",
				"SET_NAV_CHAIN"	=>	"N",
				"POPUP_DIALOG" => true,
			),
			null,
			array("HIDE_ICONS" => "Y")
		);
	}
	else
	{
?>	<tr class="section">
		<td colspan="2" align="center"><b><?=GetMessage("WD_TAB3_TITLE")?></b></td>
	</tr>
<?
	$arResult["GROUPS"] = array(); 
	$db_res = CGroup::GetList($by="sort", $order="asc", Array("ID"=>"~2"));
	if ($db_res && $res = $db_res->Fetch())
	{
		do 
		{
			$arResult["GROUPS"][$res["ID"]] = $res;
		} while ($res = $db_res->Fetch()); 
	}
	
	$arResult["PERMISSIONS_TITLE"] = Array(
		"D" => GetMessage("IB_E_ACCESS_D"),
		"R" => GetMessage("IB_E_ACCESS_R"),
		"W" => GetMessage("IB_E_ACCESS_W"),
		"X" => GetMessage("IB_E_ACCESS_X")); 
			
	if ($arIBlock["WORKFLOW"] == "Y") :
		$arResult["PERMISSIONS_TITLE"] = Array(
			"D" => GetMessage("IB_E_ACCESS_D"),
			"R" => GetMessage("IB_E_ACCESS_R"),
			"U" => GetMessage("IB_E_ACCESS_U"),
			"W" => GetMessage("IB_E_ACCESS_W"),
			"X" => GetMessage("IB_E_ACCESS_X"));
	elseif ($arIBlock["BIZPROC"] == "Y") :
		$arResult["PERMISSIONS_TITLE"] = Array(
			"D" => GetMessage("IB_E_ACCESS_D"),
			"R" => GetMessage("IB_E_ACCESS_R"),
			"U" => GetMessage("IB_E_ACCESS_U2"),
			"W" => GetMessage("IB_E_ACCESS_W"),
			"X" => GetMessage("IB_E_ACCESS_X"));
	endif;
	$arResult["PERMISSIONS_GROUP"] = CIBlock::GetGroupPermissions($iblock_id); 
	if (!array_key_exists(1, $arResult["PERMISSIONS_GROUP"]))
		$arResult["PERMISSIONS_GROUP"][1] = "X";
	
	$_REQUEST["GROUP"] = (is_array($_REQUEST["GROUP"]) ? $_REQUEST["GROUP"] : array()); 
	$selected = (array_key_exists($_REQUEST["GROUP"][2], $arResult["PERMISSIONS_TITLE"]) ? $_REQUEST["GROUP"][2] : $arResult["PERMISSIONS_GROUP"][2]); 
	$arData = array("GROUP2" => '<select name="GROUP[2]" id="GROUP_2_" onclick="if(__obj){__obj.perms_select=\'\';}">'); 
	foreach ($arResult["PERMISSIONS_TITLE"] as $key => $val)
		$arData["GROUP2"] .= '<option value="'.$key.'"'.($selected == $key ? ' selected="selected"' : '').'>'.htmlspecialcharsex($val).'</option>';
	$arData["GROUP2"] .= '</ select>';
	
?>
	<tr>
		<td width="50%" align="right"><?=GetMessage("IB_E_EVERYONE")?>:</td>
		<td width="50%"><?=$arData["GROUP2"]?></td>
	</tr>
<?

$artmp = array("" => GetMessage("IB_E_DEFAULT_ACCESS")) + $arResult["PERMISSIONS_TITLE"]; 
$arResult["GROUPS_TITLE"] = array(); 
foreach ($arResult["GROUPS"] as $key => $val)
{
	$arResult["GROUPS_TITLE"][$key] = htmlspecialcharsbx($val["NAME"]); 
	$selected = (!empty($_REQUEST["GROUP"][$key]) ? $_REQUEST["GROUP"][$key] : $arResult["PERMISSIONS_GROUP"][$key]); 
	$selected = (array_key_exists($selected, $arResult["PERMISSIONS_TITLE"]) ? $selected : ""); 
	$tmp = '<select name="GROUP['.$key.']">'; 
	foreach ($artmp as $k => $v)
		$tmp .= '<option value="'.$k.'"'.($selected == $k ? ' selected="selected"' : '').'>'.htmlspecialcharsex($v).'</option>';
	$tmp .= '</select>';
	$arData["GROUP".$key] = $tmp; 
	if (!empty($selected))
	{
?>
	<tr>
		<td align="right"><?=htmlspecialcharsbx($val["NAME"])?>:</td>
		<td><div class="wd-rights-delete" onclick="__obj.dropgroup(this);"></div><?=$arData["GROUP".$key]?></td>
	</tr>
<?	
	}
}
?>
	<tr>
		<td colspan="2" align="center"><a href="#" onclick="if (window['__obj'] != null){__obj.addgroup();} return false;"><?=GetMessage("WD_ADD_GROUP")?></a></td>
	</tr>
</table>
<?
} // original permissions
?>

<div class="buttons">
<input type="hidden" name="save" value="Y" />

<script>
function __wd_create_rights(groups, perms)
{
	this.groups_select = ''; 
	this.perms_select = ''; 
	this.groups = groups; 
	this.perms = perms; 
	this.init = function()
	{
		if (!this.groups_select || this.groups_select == "")
		{
			this.groups_select = "";
			for (var ii in this.groups)
				this.groups_select += '<option title="' + this.groups[ii] + '" value="' + ii + '">' + this.groups[ii] + '</option>'; 
			this.groups_select = '<select class="wd-rights-groups" name="GROUP_ADD[]">' + this.groups_select + '</select>'; 
		}
		if (!this.perms_select || this.perms_select == "")
		{
			var selected = document.getElementById("GROUP_2_").value; 
			var sselected = "";
			for (var ii in this.perms)
			{
				this.perms_select += '<option value="' + ii + '"' + sselected + '>' + this.perms[ii] + '</option>'; 
				sselected = (selected == ii ? ' selected="selected"' : ''); 
			}
			this.perms_select = '<select class="wd-rights-permissions" name="GROUP_ADD_PERMISSION[]">' + this.perms_select + '</select>'; 
		}
	}
	this.addgroup = function()
	{
		this.init();
		var table = document.getElementById('edit2_edit_table'); 
		
		var tableRow = table.insertRow(table.rows.length - 1);
		tableRow.style.verticalAlign = 'top'; 
	
		var groupTD = tableRow.insertCell(0);
		var permTD = tableRow.insertCell(1);
		
		groupTD.innerHTML = this.groups_select; 
		permTD.innerHTML = this.perms_select + '<div class="wd-rights-delete" onclick="__obj.dropgroup(this);"></div>'; 
		return false; 
	}
	this.dropgroup = function(el)
	{
		el.parentNode.parentNode.parentNode.removeChild(el.parentNode.parentNode);
		return false; 
	}
}
__obj = new __wd_create_rights(<?=CUtil::PhpToJSObject($arResult["GROUPS_TITLE"])?>, <?=CUtil::PhpToJSObject($arResult["PERMISSIONS_TITLE"])?>); 

function wdChangeRightsMode()
{
	if (!confirm("<?=CUtil::JSEscape(GetMessage("IB_E_RIGHTS_WARN", array("#IB_NAME#" => $arIBlock["NAME"])))?>"))
	{
		this.checked = !this.checked;
		return false;
	}
	var form = BX.WindowManager.Get().__form;
	form.appendChild(BX.create("INPUT", {'attrs': {'name' : 'reload', 'type':'hidden', 'value' : 'Y'}}));
	BX.submit(form);
}

BX.bind(BX('IB_E_RIGHTS'), "click", wdChangeRightsMode);

</script>
<input type="hidden" name="save" value="Y" />
</div>
</div>
<?
$popupWindow->EndContent();
$popupWindow->ShowStandardButtons();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
