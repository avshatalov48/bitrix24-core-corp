<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!defined('ADMIN_THEME_ID'))
{
	define('ADMIN_THEME_ID', '.default');
}
$APPLICATION->RestartBuffer();
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/public_tools.js');
CUtil::InitJSCore(array('window'));

$action = isset($_GET["ACTION"]) ? $_GET["ACTION"] : "COPY";
if (!in_array($action, array("COPY", "MOVE")))
	$action = "COPY";

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

IncludeModuleLangFile(__FILE__);

$encPath = urlencode($_GET["path"]);

$encTemplateID = urlencode($_GET["templateID"]);
$ob = $arParams["OBJECT"];
$obJSPopup = new CJSPopup(GetMessage("pub_struct_title"));
$requestUrl = WDAddPageParams($arResult['URL']['SECTIONS_DIALOG'], array("ajax_call"=>"Y"), false);

function __struct_get_file_info($ob, $abs_path, $file)
{
	$q = array("path" => str_replace(array("///", "//"),"/",$abs_path."/".$file));
	$ob->IsDir($q);
	if ($q['path'] == '/')
	{
		$files = array();
		if ($ob->arRootSection == false)
		{
			$res = $ob->PROPFIND($q, $files, array('return'=>'array'));
			$arFile = array("file"=>$file, "id" => $ob->arParams['item_id'], "name"=>$res['IBLOCK']['NAME']);
		}
		else
		{
			$arFile = array("file"=>$file, "id" => $ob->arParams['item_id'], "name"=>$ob->arRootSection['NAME']);
		}
	} else {
		$arFile = array("file"=>$file, "id" => $ob->arParams['item_id'], "name"=>$file);
	}
	if ($ob->arParams["is_dir"])
		$arFile["type"] = "D";
	if ($ob->arParams["is_file"])
		$arFile["type"] = "F";
	return $arFile;
}

function __struct_show_files($ob, $arFiles, $doc_root, $path, $open_path, $dirsonly=false)
{
	global $USER;
	$res = '';
	$hintScript = '';
	$scrDest = '';
	$scrSrc = '';
	if (!is_array($arFiles)) $arFiles = array($arFiles);
	foreach($arFiles as $arFile)
	{
		if($arFile["name"] == '' && $arFile["file"] <> "/" && $GLOBALS['arOptions']['show_all_files'] != true)
			continue;

		$full_path = rtrim($path, "/")."/".trim($arFile["file"], "/");
		$encPath = urlencode($full_path);
		$name = ($arFile["name"] <> ''? htmlspecialcharsback($arFile["name"]):$arFile["file"]);

		$md5 = md5($full_path);
		if($dirsonly)
			$md5 = "_dirs".$md5;
		$itemID = 'item'.$md5;
		$item = '';
		if($arFile["type"] == 'D')
		{
			$arPath = array($_GET['site'], $full_path);
			$arPerm = array(
				"create_file" => $USER->CanDoFileOperation("fm_create_new_file", $arPath),
				"create_folder" => $USER->CanDoFileOperation("fm_create_new_folder", $arPath),
				"edit_folder" => $USER->CanDoFileOperation("fm_edit_existent_folder", $arPath),
				"edit_perm" => $USER->CanDoFileOperation("fm_edit_permission", $arPath),
				"del_folder" => $USER->CanDoFileOperation("fm_delete_folder", $arPath),
			);
			
			$bOpenSubdir = ($open_path <> "" && (strpos($open_path."/", $full_path."/") === 0 || $arFile["file"] == "/"));
			$dirID = 'dir'.$md5;
			$item = '<div id="sign'.$md5.'" class="'.($bOpenSubdir? 'bx-struct-minus':'bx-struct-plus').'" onclick="structGetSubDir(this, \''.$dirID.'\', \''.$encPath.'\', '.($dirsonly? 'true':'false').')"></div>
				<div class="bx-struct-dir" id="icon'.$md5.'"></div>
				<div id="'.$itemID.'" __bx_path="'.$encPath.'" __bx_type="D" class="bx-struct-name"'.
				' onmouseover="structNameOver(this)" onmouseout="structNameOut(this)" onclick="structShowDirMenu(this, '.($dirsonly? 'true':'false').', '.CUtil::PhpToJSObject($arPerm).', '.$arFile['id'].')"'.
				' ondblclick="structGetSubdirAction(\'sign'.$md5.'\')">'.htmlspecialcharsEx($name).'</div>
				<div style="clear:both;"></div>
				<div id="'.$dirID.'" class="bx-struct-sub" style="display:'.($bOpenSubdir? 'block':'none').'">'.
				($bOpenSubdir? __struct_get_files($ob, $doc_root, $full_path, $open_path, $dirsonly):'').'</div>';

			$scrDest .= ($scrDest <>''? ', ':'')."'".$itemID."'";
			if($arFile["file"] <> '/')
				$scrSrc .= ($scrSrc <>''? ', ':'')."'".$itemID."', 'icon".$md5."'";
		}
		elseif($dirsonly == false)
		{
			$arPath = array($_GET['site'], $full_path);
			$arPerm = array(
				"edit_file" => $USER->CanDoFileOperation("fm_edit_existent_file", $arPath),
				"edit_perm" => $USER->CanDoFileOperation("fm_edit_permission", $arPath),
				"del_file" => $USER->CanDoFileOperation("fm_delete_file", $arPath),
			);

			if($GLOBALS['bFileman'] == true && $GLOBALS['arOptions']['show_all_files'] == true)
				$type = CFileMan::GetFileTypeEx($arFile["file"]);
			else
				$type = "";

			$item = '<div style="float:left"></div><div class="bx-struct-file'.($type <> ''? ' bx-struct-type-'.$type : '').'" id="icon'.$md5.'"></div>
				<div id="'.$itemID.'" __bx_path="'.$encPath.'" __bx_type="F" class="bx-struct-name" onmouseover="structNameOver(this)" onmouseout="structNameOut(this)" onclick="structShowFileMenu(this, '.CUtil::PhpToJSObject($arPerm).')" ondblclick="structEditFileAction(this)">'.htmlspecialcharsEx($name).'</div>
				<div style="clear:both;"></div>';

			$scrSrc .= ($scrSrc <>''? ', ':'')."'".$itemID."', 'icon".$md5."'";
		}
		if($item <> '')
			$res .= '<div class="bx-struct-item">'.$item.'</div>';

		if($GLOBALS['arOptions']['show_file_info'] == true)
		{
			$sHint = '<table cellspacing="0" border="0">'.
				'<tr><td colspan="2"><b>'.($arFile["type"] == 'D'? GetMessage("pub_struct_folder"):GetMessage("pub_struct_file")).'</b></td></tr>'.
				'<tr><td class="bx-grey">'.GetMessage("pub_struct_name").'</td><td>'.htmlspecialcharsEx($arFile["file"]).'</td></tr>'.
				($arFile["type"] == 'F'? '<tr><td class="bx-grey">'.GetMessage("pub_struct_size")."</td><td>".number_format($arFile["size"], 0, ".", ",")." ".GetMessage("pub_struct_byte").'</td></tr>':'').
				'<tr><td class="bx-grey">'.GetMessage("pub_struct_modified").'</td><td>'.htmlspecialcharsEx(ConvertTimeStamp($arFile["time"], 'FULL', $_GET['site'])).'</td></tr>';
			if(is_array($arFile["properties"]))
				foreach($arFile["properties"] as $prop_name => $prop_val)
					$sHint .= '<tr valign="top"><td class="bx-grey">'.htmlspecialcharsEx($prop_name).':</td><td>'.htmlspecialcharsEx($prop_val).'</td></tr>';
			$sHint .= '</table>';

			$hintScript .= 'window.structHint'.$itemID.' = new BXHint(\''.CUtil::JSEscape($sHint).'\', document.getElementById(\''.$itemID.'\')); ';
		}
	}
	if($hintScript <> '')
		$res .= '<script>'.$hintScript.'</script>';

	if($GLOBALS['bFileman'] == true)
		$res .= '<script>structRegisterDD(['.$scrSrc.'], ['.$scrDest.']);</script>';

	return $res;
}

function __struct_get_files($ob, $doc_root, $path="", $open_path="", $dirsonly=false)
{
	/** @var CWebDavIblock $ob */
	$arFiles = $files = array();
	$abs_path = str_replace(array("///", "//"), "/", $doc_root."/".$path);

	$options = array('path' => $abs_path, 'depth' => '10000');
	$res = $ob->PROPFIND($options, $files, array('return'=>'array'));
	foreach ($res['RESULT'] as $item)
	{
		$arFiles[] = array('type' => $item['TYPE'] == 'S'? 'D' : 'F', "file"=>$item['NAME'], "id" => $item['ID'], "name"=>$item['NAME']);
	}
	unset($item);

	return __struct_show_files($ob, $arFiles, $doc_root, $path, $open_path, $dirsonly);
}

$bFileman = CModule::IncludeModule('fileman');

$strWarning = "";
$DOC_ROOT = '/';

$arOptions = CUserOptions::GetOption("public_structure", "options", array());
if(!isset($arOptions['show_file_info']))
	$arOptions['show_file_info'] = true;

// **********************************************
//ajax requests
if($_GET['ajax_call'] == 'Y')
{
	if($_GET['load_path'] <> '')
	{
		echo __struct_get_files($ob, $DOC_ROOT, $_GET['load_path'], "", ($_GET['dirsonly']=='Y'));
	}
	elseif($_GET['reload'] == 'Y')
	{
		$arRoot = __struct_get_file_info($ob, $DOC_ROOT, "/");
		echo __struct_show_files($ob, array($arRoot), $DOC_ROOT, "", $_GET["path"], ($_GET['dirsonly']=='Y'));
	}

	if($strWarning <> "")
	{
		$obJSPopup->ShowValidationError($strWarning);
		echo '<script>jsPopup.AdjustShadow()</script>';
	}
}
?>
<script>window.structOptions = <?=CUtil::PhpToJSObject($arOptions)?>;</script>
<?
if($_GET['ajax_call'] == 'Y')
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
	die();
}
// **********************************************

?>
<script>

if (window.waiter)
{
	BX.closeWait(BX.WindowManager.Get().DIV, window.waiter);
}

window.structGetSubDir = function(el, div_id, path, dirsonly)
{
	var div = document.getElementById(div_id);
	if(!div)
		return;
	if(div.innerHTML == '')
	{
		div.innerHTML = '<?=CUtil::JSEscape(GetMessage("pub_struct_loading"))?>';
		var url = '<?=$requestUrl?>&load_path='+path+(dirsonly? '&dirsonly=Y':'');
		BX.ajax.get(url, function(result) {
			result = jsUtils.trim(result);
			div.innerHTML = result;
			if(result == '')
			{
				el.onclick = null;
				el.className = 'bx-struct-dot';
				div.style.display = 'none';
			}
		});
	}
	el.className = (el.className == 'bx-struct-plus'? 'bx-struct-minus':'bx-struct-plus');
	div.style.display = (div.style.display == 'none'? 'block':'none');
}

window.structGetSubdirAction = function(id)
{
	var el = document.getElementById(id);
	if(el)
	{
		setTimeout(function(){if(window.structMenu)	window.structMenu.PopupHide();}, 50);
		el.onclick();
	}
}

window.structReload = function(path, params)
{
	var url = '<?=$requestUrl?>&reload=Y&path='+path+(params? '&'+params:'');
	setTimeout(ShowWaitWindow, 50);
	BX.ajax.get(url, function(result) {
		jsDD.Reset();
		var container = document.getElementById('structure_content');
		if(container)
			container.innerHTML = result;
		setTimeout(CloseWaitWindow, 50);
		structReloadDirs(path);
	});
}

window.structReloadDirs = function(path)
{
	var container = document.getElementById('structure_content');
	if(!container)
		return;
	setTimeout(ShowWaitWindow, 50);
	var url = '<?=$requestUrl?>&reload=Y&dirsonly=Y&path='+path;
	BX.ajax.get(url, function(result) {
		container.innerHTML = result;
		setTimeout(CloseWaitWindow, 50);
	});
}

window.structNameOver = function(el)
{
	el.className += ' bx-struct-name-over';
}

window.structNameOut = function(el)
{
	el.className = el.className.replace(/\s*bx-struct-name-over/ig, "");
}

window.jsPopup_subdialog = new JCPopup({'suffix':'subdialog', 'zIndex':parseInt(<?=$obJSPopup->jsPopup?>.zIndex)+20});

jsPopup_editor = new JCPopup({'suffix':'editor', 'zIndex':parseInt(<?=$obJSPopup->jsPopup?>.zIndex)+20});

window.structFolderSelect = function(id)
{
	BX("wd_copy_iblock_section_id").value=id;
	structSubmit();
}

window.structFolderCreate = function(elid, id)
{
	var dlg = new BX.CAdminDialog({
		'content_url' : "<?=WDAddPageParams( 
			CComponentEngine::MakePathFromTemplate(
				$arParams["SECTION_EDIT_URL"], 
				array("ACTION" => "ADD")), 
				array("use_light_view" => "Y", "AJAX_CALL" => "Y"),
				false)
			?>".replace('#SECTION_ID#', id),
		'width' : 450,
		'height' : 40
	});
	dlg.reloadTree = function() {
		var md5 = elid.substr(elid.indexOf('_'));
		var plusID = 'sign'+md5;
		var dirID = 'dir'+md5;
		var dir = BX(dirID);
		var plus = BX(plusID);
		if (dir)
		{
			if (dir.innerHTML.length > 0) dir.innerHTML = '';
			dir.style.display = 'none';
		}
		structGetSubDir(plus, dirID, BX(elid).getAttribute('__bx_path'), true);
	};
	dlg.Show();
}


window.structShowDirMenu = function(el, dirsonly, arPerm, id)
{
	var path = el.getAttribute('__bx_path');
	var items = [
		{'ICONCLASS': 'panel-folder-props', 'TEXT': '<?=CUtil::JSEscape(GetMessage("pub_struct_folder_select"))?>', 'ONCLICK': 'structFolderSelect(\''+id+'\')', 'TITLE': '<?=CUtil::JSEscape(GetMessage("pub_struct_folder_select_title"))?>', 'DISABLED':false}
<? if ($arParams["OBJECT"]->CheckWebRights("COPY")): // TODO: check e_rights !!! ?>
		,{'ICONCLASS': 'panel-folder-props', 'TEXT': '<?=CUtil::JSEscape(GetMessage("pub_struct_folder_create"))?>', 'ONCLICK': 'structFolderCreate(\''+el.id+'\', \''+id+'\')', 'TITLE': '<?=CUtil::JSEscape(GetMessage("pub_struct_folder_create_title"))?>', 'DISABLED':false}
<? endif; ?>
	];
	window.structShowMenu(el, items, dirsonly);
}

window.structShowFileMenu = function(el, arPerm)
{
	var path = el.getAttribute('__bx_path');
	var ext = '';
	var pos = path.lastIndexOf('.');
	if(pos > -1)
		ext = path.substr(pos+1);

	var bText = false;
	var items = [];
	if(items.length > 0)
		items[items.length] = {'SEPARATOR':true};
	items[items.length] = {'ICONCLASS': 'panel-file-delete', 'TEXT': (bText? '<?=CUtil::JSEscape(GetMessage("pub_struct_file_del"))?>':'<?=CUtil::JSEscape(GetMessage("pub_struct_file_del_title"))?>'), 'ONCLICK': 'alert()', 'TITLE': '<?=CUtil::JSEscape(GetMessage("pub_struct_file_del_title1"))?>', 'DISABLED':!arPerm.del_file};
	window.structShowMenu(el, items);
}

window.structEditFileAction = function(el)
{
	var path = el.getAttribute('__bx_path');
	var pos = path.lastIndexOf('.');
	if(pos > -1)
	{
		var ext = path.substr(pos+1);
		if(ext == 'php' || ext == 'htm' || ext == 'html')
			structEditFile(path);
	}
}

window.structShowMenu = function(el, items, dirsonly)
{
	if(!window.structMenu)
	{
		window.structMenu = new PopupMenu('structure_menu');
		window.structMenu.Create(parseInt(<?=$obJSPopup->jsPopup?>.zIndex)+15);
	}

	if(window['structHint'+el.id])
		window['structHint'+el.id].Freeze();
	jsUtils.addCustomEvent('OnBeforeCloseDialog', window.structMenu.PopupHide, [], window.structMenu);
	
	var dY = 0; 	
	var dPos = {'left':0, 'right':0, 'top':-dY+1, 'bottom':-dY+1};
		
	window.structMenu.ShowMenu(el, items, false, dPos, function(){
		if(window['structHint'+el.id])
			window['structHint'+el.id].UnFreeze();
	});
}

window.structOpenDirs = function(el)
{
	if(document.getElementById('bx_struct_dirs'))
		return;
	var strDiv = <?=$obJSPopup->jsPopup?>.Get();
	var div = jsFloatDiv.Create({
		'id':'bx_struct_dirs', 
		'className':'bx-popup-form', 
		'zIndex':parseInt(<?=$obJSPopup->jsPopup?>.zIndex)+10,
		'width':250, 'height':strDiv.offsetHeight
	});

	BX.showWait(strDiv);
	BX.ajax.get(
		'<?=$requestUrl."&path=".$encPath?>&reload=Y&dirsonly=Y', 
		function(result)
		{
			var container = document.getElementById('bx_struct_dirs');
			if(container)
			{
				container.innerHTML = 
					'<div class="bx-popup-title" id="bx_popup_title_dirs"><table cellspacing="0" class="bx-width100">'+
					'<tr>'+
					'	<td class="bx-width100 bx-title-text">'+'<?=CUtil::JSEscape(GetMessage("pub_struct_sections"))?>'+'</td>'+
					'	<td class="bx-width0"><a class="bx-popup-close" href="javascript:void(0)" onclick="structCloseDirs()" title="'+'<?=CUtil::JSEscape(GetMessage("pub_struct_close"))?>'+'"></a></td>'+
					'</tr>'+
					'</table></div>'+
					'<div class="bx-popup-content" id="bx_struct_dirs_content"><div class="bx-popup-content-container" id="bx_struct_dirs_container">'+result+'</div></div>';

				var pos = jsUtils.GetRealPos(strDiv);
				var cont = document.getElementById('bx_struct_dirs_content');
				cont.style.height = pos["bottom"]-pos["top"]-31+'px';
				cont.style.width = 250-12+'px';
			
				jsDD.registerContainer(cont);

				div.style.zIndex = parseInt(<?=$obJSPopup->jsPopup?>.zIndex)+2;
				jsFloatDiv.Show(div, pos["left"]-250-1, pos["top"], 0, true);
				BX.closeWait(strDiv);
			}
		}
	);
	window.structUpdateTop = function() {div.style.top = strDiv.style.top;}
	BX.addCustomEvent(<?=$obJSPopup->jsPopup?>, 'onWindowClose', structCloseDirs);
	BX.addCustomEvent(<?=$obJSPopup->jsPopup?>, 'onWindowExpand', window.structUpdateTop);
	BX.addCustomEvent(<?=$obJSPopup->jsPopup?>, 'onWindowNarrow', window.structUpdateTop);
}

window.structCloseDirs = function()
{
	var div = document.getElementById('bx_struct_dirs');
	if(div)
	{
		jsFloatDiv.Close(div);
		div.parentNode.removeChild(div);
	}
	BX.removeCustomEvent(<?=$obJSPopup->jsPopup?>, 'onWindowClose', structCloseDirs);
	
	if (window.structUpdateTop)
	{
		BX.removeCustomEvent(<?=$obJSPopup->jsPopup?>, 'onWindowExpand', window.structUpdateTop);
		BX.removeCustomEvent(<?=$obJSPopup->jsPopup?>, 'onWindowNarrow', window.structUpdateTop);
		window.structUpdateTop = null;
	}
}

window.structSubmit = function()
{
	BX("wd_copy_iblock_section_id").parentNode.setAttribute("action", "<?=htmlspecialcharsbx($arResult["URL"]["THIS"])?>");
	BX("wd_copy_iblock_section_id").parentNode.setAttribute("method", "POST");
	window.waiter = BX.showWait(BX.WindowManager.Get().DIV);
	BX.WindowManager.Get().PostParameters();
}

</script>

<?
$obJSPopup->ShowTitlebar();
$obJSPopup->StartDescription('bx-structure');
?>
<p><b><?echo GetMessage(($action == "COPY" ? "pub_struct_desc_copy" : "pub_struct_desc_move"), array('#NAME#' => htmlspecialcharsbx(urldecode($_REQUEST["NAME"]))))?></b></p>
<br style="clear:both;" />
<?
$obJSPopup->StartContent();
if (!empty($arResult["ERROR_MESSAGE"]))
{
	if (strpos($arResult["ERROR_MESSAGE"],GetMessage("WD_FILE_ERROR4")) === false)
	{
		ShowError($arResult["ERROR_MESSAGE"]);
	}
}
?>
<div id="structure_content">
<?
	//display first level tree
	$arRoot = __struct_get_file_info($ob, $DOC_ROOT, "/");
	echo __struct_show_files($ob, array($arRoot), $DOC_ROOT, "", $_GET["path"], true);

	if (is_array($_REQUEST["ID"]))
		$_REQUEST["ID"] = $_REQUEST["ID"][0];
?>
</div>
<script>
	var md5 = '<?=md5('/')?>';
	structGetSubDir(BX('sign_dirs'+md5), 'dir_dirs'+md5, '/', true);
</script>
<input type="hidden" name="ACTION" value="<?=$action?>" />
<input type="hidden" name="AJAX" value="Y" />
<input type="hidden" name="ID[]" value="<?=htmlspecialcharsbx($_REQUEST["ID"])?>" />
<input type="hidden" name="action_button_WebDAV<?=$arParams["IBLOCK_ID"]?>" />
<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=htmlspecialcharsbx($_REQUEST["IBLOCK_SECTION_ID"])?>" id="wd_copy_iblock_section_id" />
<input type="hidden" name="overwrite" value="0" id="wd_copy_overwrite" />
<?
$obJSPopup->ShowStandardButtons(array("close"));


if (!empty($arResult["ERROR_MESSAGE"]))
{
	if (strpos($arResult["ERROR_MESSAGE"],GetMessage("WD_FILE_ERROR4")) !== false)
	{
		$msgTitle = ($action === "COPY") ? "WD_COPY_CONFIRM_TITLE" : "WD_MOVE_CONFIRM_TITLE";
?>
<script>
	WDConfirm("<?=CUtil::JSEscape(GetMessage($msgTitle))?>", "<?=CUtil::JSEscape(GetMessage("WD_MOVE_CONFIRM"))?>", function() {
		BX("wd_copy_overwrite").value = 1;
		structSubmit();
	});
</script>
<script type="text/javascript">
	BX.message({
		'wd_service_edit_doc_default': '<?= CUtil::JSEscape(CWebDavTools::getServiceEditDocForCurrentUser()) ?>'
	});
</script>
<?
	} 
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
