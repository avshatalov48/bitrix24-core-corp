<?
##############################################
# Bitrix: SiteManager						 #
# Copyright (c) 2002-2010 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################

global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/webdav/lang/", "/options.php"));
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");

$module_id = "webdav";
CModule::IncludeModule($module_id);

$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);
if($MOD_RIGHT>="R")
{

// set up form
$arAllOptions =	Array(
	Array(
		"office_extensions",
		GetMessage('WEBDAV_OPTIONS_OFFICE_EXTENSIONS'),
		".accda .accdb .accde .accdt .accdu .doc .docm .docx .dot .dotm ".
		".dotx .gsa .gta .mda .mdb .mny .mpc .mpp .mpv .mso .msproducer .pcs ".
		".pot .potm .potx .ppa .ppam .pps .ppsm .ppsx .ppt .pptm .pptx .pst .pub ".
		".rtf .sldx .xla .xlam .xlb .xlc .xld .xlk .xll .xlm .xls .xlsb .xlsm .xlsx ".
		".xlt .xltm .xltx .xlv .xlw .xps .xsf .odt .ods .odp .odb .odg .odf",
		Array("textarea",  5, 60)
	),
	Array( "hide_system_files", GetMessage('WEBDAV_OPTIONS_HIDE_SYSTEM_FILES'), "Y", Array("checkbox")),
	Array( "webdav_log", GetMessage('WEBDAV_OPTIONS_LOG'), "N", Array("checkbox")),
	Array( "webdav_socnet", GetMessage('WEBDAV_OPTIONS_SOCNET'), "Y", Array("checkbox")),
	Array( "webdav_allow_ext_doc_services_global", GetMessage('WEBDAV_OPTIONS_ALLOW_EXTERNAL_DOC_SERVICES'), "Y", Array("checkbox")),
	Array( "webdav_allow_ext_doc_services_local", GetMessage('WEBDAV_OPTIONS_ALLOW_EXTERNAL_DOC_SERVICES_LOC_POLICY'), "Y", Array("checkbox")),
	Array( "webdav_allow_autoconnect_share_group_folder", GetMessage('WEBDAV_OPTIONS_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER'), "Y", Array("checkbox")),
	Array(
		"webdav_ext_links_url",
		GetMessage('WEBDAV_OPTIONS_EXT_LINKS_URL'),
		CWebDavExtLinks::URL_DEF,
		Array("text")
	),
);

$arHistoryOptions = Array(
	Array( "bp_history_size", GetMessage('WEBDAV_OPTIONS_BP_HISTOTY_SIZE', array("#LINK#" => '/bitrix/admin/settings.php?mid=workflow')), "50", Array("text")),
	Array( "bp_history_glue", GetMessage('WEBDAV_OPTIONS_BP_HISTOTY_GLUE'), "Y", Array("checkbox")),
	Array( "bp_history_glue_period", GetMessage('WEBDAV_OPTIONS_BP_HISTOTY_GLUE_PERIOD'), "300", Array("text")),
);

if (!function_exists('wd_extensions_cleanup'))
{
	function __wd_extensions_cleanup($val)
	{
		$sExtensions = str_replace(array(",",";","\t","\n"), " ", $val);
		$arExtensions = explode(' ', $sExtensions);
		for ($i=0; $i<sizeof($arExtensions); $i++)
		{
			$arExtensions[$i] = trim($arExtensions[$i]);
			if (strlen($arExtensions[$i])>0 && substr($arExtensions[$i], 0, 1) != '.')
				$arExtensions[$i] = "." . $arExtensions[$i];
		}
		$arExtensions = array_filter(array_unique($arExtensions));
		$val = implode(" ", $arExtensions);
		return $val;
	}
}
$arFileTypes = null;
if($MOD_RIGHT>="Y" || $USER->IsAdmin())
{

	if ($REQUEST_METHOD=="GET" && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
	{
		COption::RemoveOption($module_id);
		$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
	}

	$sFileTypes = COption::GetOptionString($module_id, "file_types");
	$arFileTypes = @unserialize($sFileTypes);

	if($REQUEST_METHOD=="POST" && strlen($Update)>0 && check_bitrix_sessid())
	{
		$arOptions = array_merge($arAllOptions, $arHistoryOptions);

		foreach($arOptions as $option)
		{
			if(!is_array($option))
				continue;

			$name = $option[0];
			$val = ${$name};
			if($option[3][0] == "checkbox" && $val != "Y")
				$val = "N";
			if($option[3][0] == "multiselectbox")
				$val = @implode(",", $val);
			if ($name == "office_extensions")
			{
				$val = __wd_extensions_cleanup($val);
			}

			COption::SetOptionString($module_id, $name, $val, $option[1]);
		}

		$n = 0;
		while (isset($_POST['n'.$n]))
		{
			$nID = $_POST['n'.$n];
			if (is_array($arFileTypes))
			{
				$nIndex = false;
				foreach ($arFileTypes as $iIndex => $arFileType)
				{
					if ($arFileType['ID'] == $nID)
						$nIndex = $iIndex;
				}

				if ($nIndex !== false)
				{
					if (isset($_POST['wd_type_name_'.$n]))
						$arFileTypes[$nIndex]['NAME'] = $_POST['wd_type_name_'.$n];
					if (isset($_POST['wd_type_ext_'.$n]))
						$arFileTypes[$nIndex]['EXTENSIONS'] = $_POST['wd_type_ext_'.$n];
				}
				if (isset($_POST['remove_'.$n]))
					unset($arFileTypes[$nIndex]);
			}
			$n++;
		}
		if (!is_array($arFileTypes))
			$arFileTypes = array();
		if (isset($_POST['WD_ADD_TYPE_NAME']))
		{
			foreach ($_POST['WD_ADD_TYPE_NAME'] as $iIndex => $sNewTypeName)
			{
				$arNewType = array();
				$sNewTypeName = trim(strip_tags($sNewTypeName));
				if (strlen($sNewTypeName) <= 0) continue;
				$arNewType['NAME'] = $sNewTypeName;
				if (isset($_POST['WD_ADD_TYPE_EXT'][$iIndex]))
				{
					$sNewTypeExt = trim(strip_tags($_POST['WD_ADD_TYPE_EXT'][$iIndex]));
					if (strlen($sNewTypeExt) <= 0) continue;
					$arNewType['EXTENSIONS'] = __wd_extensions_cleanup($sNewTypeExt);
				}
				$arNewType['ID'] = substr(md5($arNewType['EXTENSIONS']), 0, 8);
				$arFileTypes[] = $arNewType;
			}
		}
		COption::SetOptionString($module_id, 'file_types', serialize($arFileTypes));
	}

} //if($MOD_RIGHT>="W"):

if (!is_array($arFileTypes))
{
	$arFileTypes = array();
}

$aTabs = array();

$aTabs[] = array("DIV" => "set", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "webdav_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET"));
$aTabs[] = array("DIV" => "history", "TAB" => GetMessage("HISTORY_TAB_SET"), "ICON" => "webdav_settings", "TITLE" => GetMessage("MAIN_HISTORY_TITLE_SET"));

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<?
$tabControl->Begin();
?>
<script>
function addNewTableRow(tableID, regexp, rindex)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length-1;
	var oRow = tbl.insertRow(cnt);
	//var col_count = tbl.rows[cnt-1].cells.length;

	var oCellName = oRow.insertCell(0);
	var oCellExt = oRow.insertCell(1);
	var oCellRem = oRow.insertCell(2);
	oCellName.innerHTML = "<input type=\"text\" name=\"WD_ADD_TYPE_NAME[]\" value=\"\" />";
	oCellExt.innerHTML = "<textarea spellcheck=\"false\" rows=\"5\" cols=\"50\" name=\"WD_ADD_TYPE_EXT[]\"></textarea>";
	oCellRem.innerHTML = "&nbsp;";
}

function WDActivateQuickEdit(elm)
{
	var aHrefs = BX.findChild(elm, {'tag': 'a'}, true, true);
	for (var j in aHrefs)
	{
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
	BX.bind(elm, 'click',	  function() { WDActivateEdit(elm);});
}


var WDChangeMode = function(fields, elm)
{
	fields = !!fields;
	localViewElements = BX.findChild(elm, {'class': 'wd-quick-view'}, true, true);
	localEditElements = BX.findChild(elm, {'class': 'wd-quick-edit'}, true, true);

	var on	= (fields ? 'block' : 'none');
	var off = (fields ? 'none' : 'block');

	for (var i in localViewElements)
		localViewElements[i].style.display = off;

	for (var i in localEditElements)
		localEditElements[i].style.display = on;
}

function WDActivateEdit(elm)
{
	WDChangeMode(true, elm.parentNode);
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

BX(function() {
	var aElements = BX.findChild(document, {'class':'wd-quick-view'}, true, true);
	for (i in aElements)
	{
		WDActivateQuickEdit(aElements[i]);
	}

	BX.addCustomEvent(document.forms.webdav_settings, 'onAutoSaveRestoreFinished', function (obAutoSave, data) { // fix autosave misunderstanding
		var aElements = BX.findChild(document.forms.webdav_settings, {'class':'wd-quick-view'}, true, true);
		for (i in aElements)
		{
			var elm = aElements[i].parentNode;
			localViewElements = BX.findChild(elm, {'class': 'wd-quick-view'}, true);
			text = (localViewElements.innerText || localViewElements.textContent);
			
			localEditElements = BX.findChild(elm, {'class': 'wd-quick-edit'}, true);
			inputField = false;
			inputFields = BX.findChild(localEditElements, {'tag': 'input'}, true, true);
			for ( j in inputFields)
			{
				if (inputFields[j].getAttribute('type') == 'text')
				{
					inputField = inputFields[j];
					break;
				}
			}
			if (! inputField)
				inputField = BX.findChild(localEditElements, {'tag': 'textarea'}, true);
			if (inputField)
			{
				if (inputField.value != text)
				{
					localViewElements.innerHTML = ((inputField.value.length > 0) ? inputField.value : '&nbsp;'); // was autosaved !
				}
			}
		}

	}, 2000);
});
</script>

<style>
#tblTYPES tr td				{vertical-align: top;}
#tblTYPES .wd-quick-edit	{display: none; width: 500px;}
#tblTYPES .wd-quick-view	{padding: 3px; border: 1px solid transparent; width:500px;}
#tblTYPES .wd-input-hover	{background-color:#F8F8F8; border: 1px solid #bbbbbb; cursor: pointer;}
textarea { word-wrap: break-word; }
#tblTYPES .wd-quick-view wbr {display:inline-block;}
</style>

<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>" name="webdav_settings">
<?$tabControl->BeginNextTab();?>
<?__AdmSettingsDrawList("webdav", $arAllOptions);?>

	<tr class="heading">
		<td colspan="2"><?=GetMessage("WEBDAV_OPTIONS_FILE_TYPES")?></td>
	</tr>
	<tr>
		<td valign="top" colspan="2">
		<table border="0" cellspacing="0" cellpadding="0" class="internal" align="center" id="tblTYPES">
			<tr class="heading">
				<td><?echo GetMessage("WEBDAV_OPTIONS_FILES_TYPE_NAME")?></td>
				<td><?echo GetMessage("WEBDAV_OPTIONS_FILES_TYPE_EXTENSIONS")?></td>
				<td><?echo GetMessage("WEBDAV_OPTIONS_FILES_TYPE_REMOVE")?></td>
			</tr>
<?
$n = 0;
foreach ($arFileTypes as $sFileTypeProps)
{
?>
			<tr>
				<td>
					<div class="wd-quick-view" style="width:auto;"><?=htmlspecialcharsbx($sFileTypeProps["NAME"])?></div>
					<div class="wd-quick-edit" style="width:auto;">
						<input type="hidden" name="n<?=$n?>" value="<?=$sFileTypeProps['ID'];?>" />
						<input type="text" name="wd_type_name_<?=$n?>" value="<?=htmlspecialcharsbx($sFileTypeProps["NAME"])?>" />
					</div>
				</td>
				<td>
					<div class="wd-quick-view"><?=str_replace(" "," <wbr/>",htmlspecialcharsbx($sFileTypeProps["EXTENSIONS"]))?></div>
					<div class="wd-quick-edit">
						<textarea spellcheck="false" rows="5" cols="50" name="wd_type_ext_<?=$n?>"><?=htmlspecialcharsbx($sFileTypeProps["EXTENSIONS"])?></textarea>
						</div>
				</td>
				<td>
					<input type="checkbox" name="remove_<?=$n?>" />
				</td>
			</tr>
<?
	$n++;
}
?>
			<tr>
				<td colspan="3" style="border:none">
				<br />
				<input type="button" value="<?echo GetMessage("WEBDAV_OPTIONS_ADD_FILE_TYPE")?>" onClick="addNewTableRow('tblTYPES', /right\[(n)([0-9]*)\]/g, 2)">
				</td>
			</tr>
		</table>
		</td>
	</tr>
<?$tabControl->BeginNextTab();?>
	<?__AdmSettingsDrawList("webdav", $arHistoryOptions);?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)."&".bitrix_sessid_get();?>";
}
BX(function() {
	try {
	var textarea = BX.findChild(document, {'attributes':{'name':'office_extensions'}}, true);
	if (textarea != null)
		textarea.spellcheck = false;
	} catch(err) {}
});
</script>
<input type="submit" name="Update" <?if ($MOD_RIGHT<"W") echo "disabled" ?> value="<?echo GetMessage("MAIN_SAVE")?>">
<input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
<input type="hidden" name="Update" value="Y">
<?=bitrix_sessid_post();?>
<input type="button" <?if ($MOD_RIGHT<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?
}
?>
