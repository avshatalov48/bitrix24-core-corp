<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

CPageTemplate::IncludeLangFile(__FILE__);

class CLibraryPageTemplate
{
	function GetDescription()
	{
		return array(
			"name"=>GetMessage("library_wizard_name"), 
			"description"=>GetMessage("library_wizard_title"),
			"icon"=>"/bitrix/templates/.default/page_templates/library/images/icon_webdav.gif",
			"modules"=>array("webdav", "iblock"),
			"type"=>"section",
		);
	}
	
	function GetFormHtml()
	{
		if(!CModule::IncludeModule('iblock'))
			return '';

		//name

		$libNameTpl = GetMessage("library_wizard_lib_name_val");
		$libSearchVal = -1;
		do {
			$libSearchVal++;
			$libName = $libNameTpl;
			if ($libSearchVal > 0) 
				$libName .= " (" . $libSearchVal . ")";
			$dbRes = CIBlock::GetList(array(), array("NAME" => $libName));
		} while (($dbRes && $arResLibName = $dbRes->Fetch()));

		$s = '
<tr class="section">
	<td colspan="2">'.GetMessage("library_wizard_settings").'</td>
</tr>
<tr>
	<td class="bx-popup-label bx-width30">'.GetMessage("library_wizard_lib_name").'</td>
	<td>
		<input type="text" name="library_TITLE" value="'.$libName.'" '.( 'onkeyup="library_CheckIBlockName(this)"' ).' style="width:90%"><div class="errortext"></div>
	</td>
	<script>
	window.library_CheckIBlockName = function(el)
	{
		var excludeChars = new RegExp("[\\\\\\\\{}/:\\*\\?|%&~]");
		var res = ""; 
		if (el.value)
		{
			if (el.value.search(excludeChars) != -1)
			{
				res = "'.CUtil::JSEscape(GetMessage("library_wizard_iblock_name_error1")).'";
			}
		}
		el.nextSibling.innerHTML = res;

		BX("btn_popup_next").disabled = (res.length > 0);
		BX("btn_popup_finish").disabled = (res.length > 0);
	}
	</script>
</tr>
';
		//resource
if (isset($_REQUEST['mode']))
{
	if ($_REQUEST['mode'] == 'iblock')
	{
		$s .= "<input type=\"hidden\" name=\"library_resource_type\" value = \"iblock\" />";
	}
	elseif ($_REQUEST['mode'] == 'folder')
	{
		$s .= "<input type=\"hidden\" name=\"library_resource_type\" value = \"folder\" />";
	}
}
else
{
		$s .= '
<tr>
	<td class="bx-popup-label bx-width30">'.GetMessage("library_wizard_lib_resource").'</td>
	<td>
<script>
window.library_BuildSelectResource = function()
{
	var el = BX("library_resource_type");
	var docroot = "'.htmlspecialcharsEx(str_replace("//", "/", $_REQUEST["path"]."/")).'";
	BX("library_resource_folder").style.display = (el.value == "folder" ? "":"none");
	BX("library_resource_iblock").style.display = (el.value == "iblock" ? "":"none");
	if (el.value == "folder")
		BX("bx_new_resource_folder").value = docroot + BX("bx_new_page_name").value + "_files";
}
BX( function() {
	BX.bind(BX("library_resource_type"), (BX.browser.IsIE() ? "click" : "change"), window.library_BuildSelectResource);
	window.library_BuildSelectResource();
});
</script>
	';
	$s .= '
		<select id="library_resource_type" name="library_resource_type" onclick="library_BuildSelectResource(this);" style="width:90%">
			<option value="folder"'.($_REQUEST["library_resource_type"] == "folder" ? ' selected="selected"' : '').'>'.GetMessage("library_wizard_lib_resource_folder").'</option>
			<option value="iblock"'.($_REQUEST["library_resource_type"] != "folder" ? ' selected="selected"' : '').'>'.GetMessage("library_wizard_lib_resource_iblock").'</option>
		</select>
';
$s .= '	</td>
</tr>
';
}
		//folder
		$sHide = ((isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'folder') ? '' : "display:none");
		$s .= '
<tbody id="library_resource_folder" style="'.$sHide.'">
<tr>
	<td class="bx-popup-label bx-width30">'.GetMessage("library_wizard_path_to_folder").'</td>
	<td>
<script>
window.library_CheckFolderPath = function(el)
{
	var res = ""; 
	if (el.value)
	{
		if (el.value.substr(0, 1) != "/")
			res = "'.CUtil::JSEscape(GetMessage("library_wizard_path_to_folder_error1")).'";
		else if (el.value.substr(0, 7) == "/bitrix")
			res = "'.CUtil::JSEscape(GetMessage("library_wizard_path_to_folder_error2")).'"; 
	}
	el.nextSibling.innerHTML = res;
}
</script>
		<input type="text" id="bx_new_resource_folder" name="library_FOLDER" value="'.htmlspecialcharsEx(str_replace("//", "/", $_REQUEST["path"]."/")).'" onkeyup="library_CheckFolderPath(this)" style="width:90%">'.
		'<div class="errortext"></div>'.
	'</td>
</tr>';
		//user rights
		$script = '<select onchange="library_SetGroupIDFolder(this)"><option>'.GetMessage("library_wizard_group_select").'</option>';
		$db_res = CGroup::GetList($by = "c_sort", $order = "asc");
		while($res = $db_res->Fetch())
			if($res["ID"] <> 1)
				$script .= '<option value="'.$res["ID"].'">'.htmlspecialcharsbx($res["NAME"])." [".$res["ID"]."]".'</option>';
		$script .= '</select>';
		
		$perm = 
		'<select name="">
			<option value="R">'.GetMessage("library_wizard_perm_read").'</option>
			<option value="W">'.GetMessage("library_wizard_perm_write").'</option>
		</select>';
		$s .= '
<tr class="section" id="library_folder_permissions1"><td colspan="2">'.GetMessage("library_wizard_perm_folder").'</td></tr>
<tr id="library_folder_permissions2"><td colspan="2">
<script>
window.library_SetGroupIDFolder = function(el)
{
	var td = jsUtils.FindParentObject(el, "td");
	td = jsUtils.FindNextSibling(td, "td");
	var sel = jsUtils.FindChildObject(td, "select");
	sel.name = "library_FOLDER_PERMISSION["+el.value+"]";
}
window.library_AddRightsFolder = function()
{
	var tbl = document.getElementById("library_rights_table_folder");

	//Create new row
	var tableRow = tbl.insertRow(tbl.rows.length);

	var groupTD = tableRow.insertCell(0);
	var permTD = tableRow.insertCell(1);
	
	groupTD.innerHTML = \''.CUtil::JSEscape($script).'\';
	permTD.innerHTML = \''.CUtil::JSEscape($perm).'\';
}
</script>
<center>
		<table cellpadding="2" cellspacing="0" border="0" align="center" id="library_rights_table_folder">
			<tr>
				<td>'.$script.'</td>
				<td>'.$perm.'</td>
			</tr>
		</table>
</center>
		<p style="margin:8px 0px 8px 0px;"><a href="javascript:library_AddRightsFolder()">'.GetMessage("library_wizard_perm_add").'</a></p>
	</td>
</tr>
';	'
</tbody>'; 

		//iblock
		$sHide = ((isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'iblock') ? '' : "display:none");
		if (!isset($_REQUEST['mode'])) $sHide = "";
		$s .= '
<tbody id="library_resource_iblock" style="'.$sHide.'">
<tr>
	<td class="bx-popup-label">'.GetMessage("library_wizard_iblock_type").'</td>
	<td><select name="library_IBLOCK_TYPE" onchange="library_BuildSelect(this)">
';		
		//iblock types and blocks
		$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
		$sFirstType = "";
		while ($arr=$rsIBlockType->Fetch())
			if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
			{
				if($sFirstType == "")
					$sFirstType = $arr["ID"];
				$s .= '<option value="'.htmlspecialcharsbx($arr["ID"]).'"'.($arr["ID"] == 'library'? ' selected':'').'>'.htmlspecialcharsbx($ar["NAME"]." [".$arr["ID"]."]").'</option>';
			}
		$s .= '
		</select>
	</td>
</tr>
<tr>
	<td class="bx-popup-label" style="vertical-align:top !important;">'.GetMessage("library_wizard_iblock").'</td>
	<td>
<script>
window.library_iblocks = {';
		
		$arIBlock=array();
		$rsIBlock = CIBlock::GetList(Array("name" => "asc"), Array("ACTIVE"=>"Y"));
		while($arr=$rsIBlock->Fetch())
			if(CIBlock::GetPermission($arr["ID"]) >= "X")
				$arIBlock[$arr["IBLOCK_TYPE_ID"]][] = array("ID"=>$arr["ID"], "NAME"=>$arr["NAME"]." [".$arr["ID"]."]");

		$sT = "";
		foreach($arIBlock as $type=>$arBlock)
		{
			$sT .= ($sT <> ""? ", ":"")."'".CUtil::JSEscape($type)."': [";
			
			$sBl = "";
			foreach($arBlock as $block)
				$sBl .= ($sBl <> ""? ",":"")."{'ID': '".$block["ID"]."', 'NAME':'".CUtil::JSEscape($block["NAME"])."'}";
			
			$sT .= $sBl."]";
		}
		
		$s .= $sT.'};

window.library_BuildSelect = function(el)
{
	var sel = el.form.library_IBLOCK_ID;
	var i;
	for(i=sel.length-1; i>=0; i--)
		sel.remove(i);
	if(window.library_iblocks[el.value])
	{
		for(i=0; i<window.library_iblocks[el.value].length; i++)
		{
			var newoption = new Option(window.library_iblocks[el.value][i].NAME, window.library_iblocks[el.value][i].ID, false, false);
			sel.options[sel.length] = newoption;
		}
	}
}

window.library_NewIblockClick = function(el)
{
	el.form.library_IBLOCK_ID.disabled = (el.value == "Y");
	document.getElementById("library_permissions1").style.display = (el.value == "Y"? "":"none");
	document.getElementById("library_permissions2").style.display = (el.value == "Y"? "":"none");
}
</script>

<input type="radio" name="library_NEW_IBLOCK" value="Y" id="library_NEW_IBLOCK_Y" checked onclick="library_NewIblockClick(this);"><label for="library_NEW_IBLOCK_Y">'.GetMessage("library_wizard_iblock_new").'</label><br>
<input type="radio" name="library_NEW_IBLOCK" value="N" id="library_NEW_IBLOCK_N" onclick="library_NewIblockClick(this);"><label for="library_NEW_IBLOCK_N">'.GetMessage("library_wizard_iblock_select").'</label><br>

<select name="library_IBLOCK_ID" disabled>
';
		$type = (isset($arIBlock['library'])? 'library':$sFirstType);
		foreach($arIBlock[$type] as $arBlock)
			$s .= '<option value="'.htmlspecialcharsbx($arBlock["ID"]).'">'.htmlspecialcharsbx($arBlock["NAME"]).'</option>';
		
		$s .= '</select></td>
</tr>
';
		//user rights
		$script = '<select onchange="library_SetGroupID(this)"><option>'.GetMessage("library_wizard_group_select").'</option>';
		$db_res = CGroup::GetList($by = "c_sort", $order = "asc");
		while($res = $db_res->Fetch())
			if($res["ID"] <> 1)
				$script .= '<option value="'.$res["ID"].'">'.htmlspecialcharsbx($res["NAME"])." [".$res["ID"]."]".'</option>';
		$script .= '</select>';
		
		$perm = '<select name="">
			<option value="R">'.GetMessage("library_wizard_perm_read").'</option>
			<option value="U">'.GetMessage("library_wizard_perm_bp").'</option>
			<option value="W">'.GetMessage("library_wizard_perm_write").'</option>
		</select>';

		$s .= '
<tr class="section" id="library_permissions1" style="display:;">
	<td colspan="2">'.GetMessage("library_wizard_perm_iblock").'</td>
</tr>
<tr id="library_permissions2" style="display:;">
	<td colspan="2">
<script>
window.library_SetGroupID = function(el)
{
	var td = jsUtils.FindParentObject(el, "td");
	td = jsUtils.FindNextSibling(td, "td");
	var sel = jsUtils.FindChildObject(td, "select");
	sel.name = "library_PERMISSION["+el.value+"]";
}

window.library_AddRights = function()
{
	var tbl = document.getElementById("library_rights_table");

	//Create new row
	var tableRow = tbl.insertRow(tbl.rows.length);

	var groupTD = tableRow.insertCell(0);
	var permTD = tableRow.insertCell(1);
	
	groupTD.innerHTML = \''.CUtil::JSEscape($script).'\';
	permTD.innerHTML = \''.CUtil::JSEscape($perm).'\';
}
</script>
<center>
		<table cellpadding="2" cellspacing="0" border="0" align="center" id="library_rights_table">
			<tr>
				<td>'.$script.'</td>
				<td>'.$perm.'</td>
			</tr>
		</table>
</center>
		<p style="margin:8px 0px 8px 0px;"><a href="javascript:library_AddRights()">'.GetMessage("library_wizard_perm_add").'</a></p>
	</td>
</tr>
';
		
		//tags			
		if(IsModuleInstalled("search"))
		{
			$s .= '
<tr class="section">
	<td colspan="2">'.GetMessage("library_wizard_tags").'</td>
</tr>
<tr>
	<td class="bx-popup-label"><label for="library_SHOW_TAGS">'.GetMessage("library_wizard_tags_show").'</label></td>
	<td><input type="checkbox" name="library_SHOW_TAGS" id="library_SHOW_TAGS" value="Y" checked></td>
</tr>
<tr>
	<td class="bx-popup-label">'.GetMessage("library_wizard_tags_num").'</td>
	<td><input type="text" name="library_TAGS_PAGE_ELEMENTS" value="50" style="width:90%"></td>
</tr>
';
		}
		
		//comments based on forum
		if($GLOBALS['APPLICATION']->GetGroupRight("forum") >= "W" && CModule::IncludeModule("forum"))
		{
			$s .= '
<tr class="section">
	<td colspan="2">'.GetMessage("library_wizard_comments").'</td>
</tr>
<tr>
	<td class="bx-popup-label"><label for="library_USE_COMMENTS">'.GetMessage("library_wizard_comments_allow").'</label></td>
	<td>
<script>
window.LibraryCommentsClick = function(el)
{
	document.getElementById("labrary_comments").style.display = (el.checked? "":"none");

	var bNew = document.getElementById("library_NEW_FORUM_Y").checked;
	document.getElementById("library_forum_permissions1").style.display = (el.checked && bNew? "":"none");
	document.getElementById("library_forum_permissions2").style.display = (el.checked && bNew? "":"none");
}

window.library_NewForumClick = function(el)
{
	if(el.form.library_FORUM_ID)
		el.form.library_FORUM_ID.disabled = (el.value == "Y");
	document.getElementById("library_forum_permissions1").style.display = (el.value == "Y"? "":"none");
	document.getElementById("library_forum_permissions2").style.display = (el.value == "Y"? "":"none");
}
</script>
		<input type="checkbox" name="library_USE_COMMENTS" id="library_USE_COMMENTS" value="Y" onclick="LibraryCommentsClick(this);">
	</td>
</tr>
<tr id="labrary_comments" style="display:none;">
	<td class="bx-popup-label" style="vertical-align:top !important;">'.GetMessage("library_wizard_forum").'</td>
	<td>
		<input type="radio" name="library_NEW_FORUM" value="Y" id="library_NEW_FORUM_Y" checked onclick="library_NewForumClick(this);"><label for="library_NEW_FORUM_Y">'.GetMessage("library_wizard_forum_new").'</label><br>
';
				$db_res = CForumNew::GetList(array(), array());
				if($db_res && $res=$db_res->Fetch())
				{
					$s .= '
		<input type="radio" name="library_NEW_FORUM" value="N" id="library_NEW_FORUM_N" onclick="library_NewForumClick(this);"><label for="library_NEW_FORUM_N">'.GetMessage("library_wizard_forum_select").':</label><br>
		<select name="library_FORUM_ID" style="width:100%" disabled>';
					do 
						$s .= '<option value="'.$res["ID"].'">'.htmlspecialcharsbx($res["NAME"])." [".$res["ID"]."]".'</option>';
					while ($res = $db_res->Fetch());
					$s .= '</select>';
				}
				else
				{
					$s .= '
		<input type="radio" name="library_NEW_FORUM" value="N" id="library_NEW_FORUM_N" disabled><label for="library_NEW_FORUM_N" disabled>'.GetMessage("library_wizard_forum_select").'</label><br>
';
				}
				$s .= '
	</td>
</tr>
';
		//forum user rights
		$script = '<select onchange="library_SetForumGroupID(this)"><option>'.GetMessage("library_wizard_group_select").'</option>';
		$db_res = CGroup::GetList($by = "c_sort", $order = "asc");
		while($res = $db_res->Fetch())
			if($res["ID"] <> 1)
				$script .= '<option value="'.$res["ID"].'">'.htmlspecialcharsbx($res["NAME"])." [".$res["ID"]."]".'</option>';
		$script .= '</select>';
		
		$perm = '<select name="">
			<option value="E">'.GetMessage("library_wizard_perm_forum_read").'</option>
			<option value="M">'.GetMessage("library_wizard_perm_forum_write").'</option>
		</select>';

		$s .= '
<tr class="section" id="library_forum_permissions1" style="display:none;">
	<td colspan="2">'.GetMessage("library_wizard_perm_forum").'</td>
</tr>
<tr id="library_forum_permissions2" style="display:none;">
	<td colspan="2">
<script>
window.library_SetForumGroupID = function(el)
{
	var td = jsUtils.FindParentObject(el, "td");
	td = jsUtils.FindNextSibling(td, "td");
	var sel = jsUtils.FindChildObject(td, "select");
	sel.name = "library_FORUM_PERMISSION["+el.value+"]";
}

window.library_AddForumRights = function()
{
	var tbl = document.getElementById("library_forum_rights_table");

	//Create new row
	var tableRow = tbl.insertRow(tbl.rows.length);

	var groupTD = tableRow.insertCell(0);
	var permTD = tableRow.insertCell(1);
	
	groupTD.innerHTML = \''.CUtil::JSEscape($script).'\';
	permTD.innerHTML = \''.CUtil::JSEscape($perm).'\';
}
</script>
<center>
		<table cellpadding="2" cellspacing="0" border="0" align="center" id="library_forum_rights_table">
			<tr>
				<td>'.$script.'</td>
				<td>'.$perm.'</td>
			</tr>
		</table>
</center>
		<p style="margin:8px 0px 8px 0px;"><a href="javascript:library_AddForumRights()">'.GetMessage("library_wizard_perm_add").'</a></p>
	</td>
</tr>
</tbody>
';
		}
		return $s;
	}

	function GetContent($arParams)
	{
		if(!CModule::IncludeModule('iblock'))
			return false;

		if ($_POST['library_resource_type'] != "folder")
		{
			//iblock
			$iblock_type = '';
			$iblock_id = 0;
			if($_POST['library_IBLOCK_TYPE'] <> '')
			{
				$res = CIBlockType::GetByID($_POST['library_IBLOCK_TYPE']);
				if($res_arr = $res->Fetch())
					$iblock_type = $res_arr["ID"];
				if($iblock_type <> '')
				{
					if($_POST['library_NEW_IBLOCK'] == 'Y')
					{
						//new iblock
						$ib = new CIBlock;
						$arFields = Array(
							"ACTIVE"=>"Y",
							"VERSION"=>1,
							"LIST_PAGE_URL"=>$arParams['path'],
							"DETAIL_PAGE_URL"=>$arParams['path'].'element/view/#ID#/',
							"NAME"=>$_POST['library_TITLE'],
							"IBLOCK_TYPE_ID"=>$iblock_type,
							"LID"=>array($arParams['site']),
							"SORT"=>"500",
							"WORKFLOW"=>"N",
							"BIZPROC"=>"N",
							"SECTION_CHOOSER"=>"L",
						);
					
						if(is_array($_POST['library_PERMISSION']))
						{
							$arPerm = array();;
							foreach($_POST['library_PERMISSION'] as $grp=>$perm)
							{
								if($perm == 'R' || $perm == 'U' || $perm == 'W')
								{
									$arPerm[$grp] = $perm;
								}

								if ($perm == 'U')
								{
									$arFields['BIZPROC'] = 'Y';
								}
							}
							$arFields["GROUP_ID"] = $arPerm;
						}
					
						$iblock_id = $ib->Add($arFields);
					}
					elseif(intval($_POST['library_IBLOCK_ID']) > 0)
					{
						//existing iblock: need check permissions
						if(CIBlock::GetPermission($_POST['library_IBLOCK_ID']) >= "X")
							$iblock_id = intval($_POST['library_IBLOCK_ID']);
					}
				}
			}
				
			//forum for comments
			$forum_id = 0;
			if($_POST['library_USE_COMMENTS'] == 'Y')
			{
				if($_POST['library_NEW_FORUM'] == 'Y')
				{
					CModule::IncludeModule('forum');
	
					//new forum
					$arFields = Array(
						"NAME" => GetMessage("library_wizard_forum_name")." \"".$_POST['library_TITLE']."\"",
						"SITES" => array($arParams['site']=>$arParams["path"]."element/view/#PARAM2#/"), 
						"ACTIVE" => "Y",
						"INDEXATION"=>"N",
						"SORT" => 150,
						"ALLOW_ANCHOR" => "Y",
						"ALLOW_BIU" => "Y",
						"ALLOW_IMG" => "Y",
						"ALLOW_LIST" => "Y",
						"ALLOW_QUOTE" => "Y",
						"ALLOW_CODE" => "Y",
						"ALLOW_FONT" => "Y",
						"ALLOW_SMILES" => "Y",
						"ALLOW_TOPIC_TITLED" => "Y",
					);
			
					if(is_array($_POST['library_FORUM_PERMISSION']))
					{
						$arPerm = array();
						foreach($_POST['library_FORUM_PERMISSION'] as $grp=>$perm)
							if($perm == 'E' || $perm == 'M')
								$arPerm[$grp] = $perm;
						$arFields["GROUP_ID"] = $arPerm;
					}
	
					$forum_id = CForumNew::Add($arFields);
				}
				elseif(intval($_POST['library_FORUM_ID']) > 0)
				{
					$forum_id = intval($_POST['library_FORUM_ID']);
				}
			}
	
			//file size
			$iUploadMaxFilesize = intval(ini_get('upload_max_filesize'));
			$iPostMaxSize = intval(ini_get('post_max_size'));
			$iUploadMaxFilesize = min($iUploadMaxFilesize, $iPostMaxSize);
	
			//bizproc templates
			if($_POST['library_NEW_IBLOCK'] == 'Y' && $iblock_id > 0 && CModule::IncludeModule("bizproc"))
			{
				
				$documentType = array("webdav", "CIBlockDocumentWebdav", "iblock_".$iblock_id);
				
				if (!function_exists("__wd_replace_user_and_groups"))
				{
					function __wd_replace_user_and_groups(&$val, $key, $params = array())
					{
						if ($key == "MailText")
						{
							$val = str_replace(
								"/company/personal/bizproc/{=Workflow:id}/", 
								$params["path"], 
								$val);
						}
						return true;
					}
				}
				
				if($handle = opendir($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bizproc/templates'))
				{
					$arr = array(
						"path" => str_replace("//", "/", $arParams["path"]."/webdav_bizproc_view/{=Document:ID}/"));
					while(false !== ($file = readdir($handle)))
					{
						if(!is_file($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bizproc/templates/'.$file))
							continue;
		
						$arFields = false;
						include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bizproc/templates/'.$file);
						
						array_walk_recursive($arFields["TEMPLATE"], "__wd_replace_user_and_groups", $arr);
						
						if ($file == "status.php")
						{
							$arFields["AUTO_EXECUTE"] = CBPDocumentEventType::Create;
						}
						
						if(is_array($arFields))
						{
							$arFields["DOCUMENT_TYPE"] = $documentType;
							$arFields["SYSTEM_CODE"] = $file;
							if(is_object($GLOBALS['USER']))
								$arFields["USER_ID"] = $GLOBALS['USER']->GetID();
							try
							{
								CBPWorkflowTemplateLoader::Add($arFields);
							}
							catch (Exception $e)
							{
							}
						}
					}
					closedir($handle);
				}
			}

			$s = 
'<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>
<?$APPLICATION->IncludeComponent("bitrix:webdav", ".default", array(
	"RESOURCE_TYPE" => "IBLOCK", 
	"IBLOCK_TYPE" => "'.EscapePHPString($iblock_type).'",
	"IBLOCK_ID" => "'.intval($iblock_id).'",
	"NAME_FILE_PROPERTY" => "FILE",
	"REPLACE_SYMBOLS" => "N",
	"USE_AUTH" => "Y",
	"UPLOAD_MAX_FILESIZE" => "'.$iUploadMaxFilesize.'",
	"UPLOAD_MAX_FILE" => "4",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "'.EscapePHPString($arParams["path"]).'",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "Y",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "N",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"COLUMNS" => array(
		0 => "NAME",
		1 => "TIMESTAMP_X",
		2 => "USER_NAME",
		3 => "FILE_SIZE",
		4 => "WF_STATUS_ID",
		5 => "",
	),
	"PAGE_ELEMENTS" => "50",
	"PAGE_NAVIGATION_TEMPLATE" => "",
	"STR_TITLE" => "'.EscapePHPString($_POST['library_TITLE']).'",
	"SET_TITLE" => "Y",
	"DISPLAY_PANEL" => "N",
	"SHOW_TAGS" => "'.($_POST['library_SHOW_TAGS'] == 'Y'? 'Y':'N').'",
	"TAGS_PAGE_ELEMENTS" => "'.(intval($_POST['library_TAGS_PAGE_ELEMENTS']) > 0? intval($_POST['library_TAGS_PAGE_ELEMENTS']):50).'",
	"TAGS_PERIOD" => "",
	"TAGS_INHERIT" => "Y",
	"TAGS_FONT_MAX" => "30",
	"TAGS_FONT_MIN" => "14",
	"TAGS_COLOR_NEW" => "486DAA",
	"TAGS_COLOR_OLD" => "486DAA",
	"TAGS_SHOW_CHAIN" => "Y",
	"USE_COMMENTS" => "'.($_POST['library_USE_COMMENTS'] == 'Y'? 'Y':'N').'",
	"FORUM_ID" => "'.intval($forum_id).'",
	"PATH_TO_SMILE" => "/bitrix/images/forum/smile/",
	"USE_CAPTCHA" => "Y",
	"PREORDER" => "Y",
	"AJAX_OPTION_ADDITIONAL" => "",
	"SEF_URL_TEMPLATES" => array(
		"user_view" => "/company/personal/user/#USER_ID#/",
		"sections" => "#PATH#",
		"section_edit" => "folder/edit/#SECTION_ID#/#ACTION#/",
		"element" => "element/view/#ELEMENT_ID#/",
		"element_edit" => "element/edit/#ACTION#/#ELEMENT_ID#/",
		"element_history" => "element/history/#ELEMENT_ID#/",
		"element_history_get" => "element/historyget/#ELEMENT_ID#/#ELEMENT_NAME#",
		"element_upload" => "element/upload/#SECTION_ID#/",
		"help" => "help",
		"search" => "search/",
	)
	),
	false
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>';
			return $s;
		}
		else
		{
			$_REQUEST["library_FOLDER"] = strtolower(str_replace("//", "/", "/".$_REQUEST["library_FOLDER"]."/"));
			CheckDirPath($_SERVER['DOCUMENT_ROOT'].$_REQUEST["library_FOLDER"], true); 
			if (!empty($_REQUEST["library_FOLDER_PERMISSION"]) && is_array($_REQUEST["library_FOLDER_PERMISSION"]))
			{
				$GLOBALS["APPLICATION"]->SetFileAccessPermission($_REQUEST["library_FOLDER"], $_REQUEST["library_FOLDER_PERMISSION"]);
			}
			$s = 
'<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>
<?$APPLICATION->IncludeComponent("bitrix:webdav", ".default", array(
	"RESOURCE_TYPE" => "FOLDER",
	"FOLDER" => "'.EscapePHPString($_REQUEST["library_FOLDER"]).'",
	"USE_AUTH" => "Y",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" =>  "'.EscapePHPString($arParams["path"]).'",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"COLUMNS" => array(
		0 => "NAME",
		1 => "FILE_SIZE",
		2 => "TIMESTAMP_X",
		4 => "",
	),
	"SET_TITLE" => "Y"
	),
	false
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>';
			return $s;
		}
	}
}

$pageTemplate = new CLibraryPageTemplate;
?>
