<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
if(defined('BX24_HOST_NAME'))
{
	die;
}
$dir = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/")));
__IncludeLang($dir."lang/".LANGUAGE_ID."/disk_sections_tree.php");
CModule::IncludeModule("webdav");
$popupWindow = new CJSPopup('', '');
CUtil::InitJSCore(array('window', 'ajax'));
if(!function_exists("__UnEscape"))
{
	function __UnEscape(&$item, $key)
	{
		if (is_array($item))
			array_walk($item, '__UnEscape');
		elseif (preg_match("/^.{1}/su", $item) == 1)
			$item = $GLOBALS["APPLICATION"]->ConvertCharset($item, "UTF-8", SITE_CHARSET);
	}
}

function __WDGetSectionsTree($base, $active)
{
	if (!function_exists("__sort_array_folder"))
	{
		function __sort_array_folder($res1, $res2)
		{
			global $by, $order;
			InitSorting();
			if (empty($by))
			{
				$by = "NAME"; $order = "ASC"; 
			}
			$by = strtoupper($by); 
			$order = strtoupper($order); 

			$by = (is_set($res1, $by) ? $by : "NAME"); 

			if ($order == "ASC")
				return ($res1[$by] < $res2[$by] ? -1 : 1); 
			return ($res1[$by] < $res2[$by] ? 1 : -1); 
		}
	}

	if (!function_exists("__get_folder_tree"))
	{
		function __get_folder_tree($path, $bCheckFolders = false)
		{
			static $io = false;
			if ($io === false)
				$io = CBXVirtualIo::GetInstance();

			$path = $io->CombinePath($path, '/');
			$arSection = array();
			$bCheckFolders = ($bCheckFolders === true);

			$dir = $io->GetDirectory($path);
			if (!$dir->IsExists())
				return false;

			$arChildren = $dir->GetChildren();
			foreach($arChildren as $node)
			{
				if ($node->IsDirectory())
				{
					if ($bCheckFolders)
						return true;

					$filename = $node->GetName();
					if (preg_match("/^\..*/",$filename))
						continue; 
					$arSection[$filename] = array(
						"NAME" => $filename,
						"HAS_DIR" => __get_folder_tree($node->GetPathWithName(), true));
				}
			}

			if ($bCheckFolders)
				return false;
			uasort($arSection, "__sort_array_folder");

			return $arSection;
		}
	}

	$arResult = $arPath = $arActive = array();
	$arActive = array_filter(explode("/", $active));
	$arResult = __get_folder_tree($base);

	if (
		! empty($arActive)
		&& is_array($arResult)
	)
	{
		$key = reset($arActive);
		do
		{
			$arPath[] = $key;
			$key_title = str_pad("", count($arPath) - 1, "*").$key;
			$key_number = array_search($key_title, array_keys($arResult));

			/**
			 * if it is used array_slice then keys in associative massive sort of "2" or "11" became natural from 0.
				$arEnd = array_slice($arResult, $key_number + 1);
				$arResult = array_slice($arResult, 0, $key_number + 1);
			 * That it is why it will be used this code.
			*/
			$arResultFirst = array();

			if ($key_number !== false)
			{
				while ($key_number >= 0)
				{
					$res = array_shift($arResult);
					$arResultFirst[(intval($res["DEEP"]) > 0 ? str_pad("", $res["DEEP"], "*") : "").$res["NAME"]] = $res;
					$key_number--;
				}
			}
			$arSections = __get_folder_tree($base."/".implode("/", $arPath)); 

			$prefix = str_pad("", count($arPath), "*"); 
			foreach ($arSections as $val)
				$arResultFirst[$prefix.$val["NAME"]] = $val + array("DEEP" => count($arPath)); 
			$arResult = $arResultFirst + $arResult; 
		} while ($key = next($arActive)); 
	}
	return $arResult; 
}

/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["FOLDER"] = trim($_REQUEST["folder"]); 
	$io = CBXVirtualIo::GetInstance();
	$arParams["ACTIVE"] = $io->CombinePath(trim($_REQUEST["active"])); 
	$ob = new CWebDavFile($arParams, ""); 
	if (!$ob->CheckRights("MOVE", false, $path = ""))
		$popupWindow->ShowError(GetMessage("WD_ACCESS_DENIED")); 

	//CWebDavFile::RegisterVirtualIOCompatibility($ob->real_path_full);

	$arParams["ELEMENT_ID"] = CUtil::JSEscape(empty($_REQUEST["element_id"]) ? "WD_IBLOCK_SECTION_ID" : $_REQUEST["element_id"]); 
/********************************************************************
				/Input params
********************************************************************/


/********************************************************************
				Data
********************************************************************/
	if ($_REQUEST["return_array"] == "Y")
	{
		array_walk($_REQUEST, '__UnEscape');
		//$_REQUEST["active"] = str_replace(array("///", "//"), "/", "/".$_REQUEST["active"]."/");
		$result = array(
			"dir_id" => md5($arParams["ACTIVE"]), //md5($_REQUEST["active"]),
			"folder" => $arParams["ACTIVE"], //$_REQUEST["active"],
			"subdirs" => __WDGetSectionsTree($ob->real_path_full . $arParams["ACTIVE"], "")); //__WDGetSectionsTree($ob->real_path_full.$_REQUEST["active"], ""));
		if (empty($result["subdirs"]))
			unset($result["subdirs"]); 
		else
		{
			foreach ($result["subdirs"] as $key => $res)
			{
				//$result["subdirs"][$key]["PATH"] = $_REQUEST["active"].$res["NAME"]."/";
				$result["subdirs"][$key]["PATH"] = $arParams["ACTIVE"] . $res["NAME"]."/";
				$result["subdirs"][$key]["ID"] = md5($result["subdirs"][$key]["PATH"]); 
			}
		}
		header(str_replace(array("\r", "\n"), "", 'Content-type: application/json'), true);
		print_r(CUtil::PhpToJSObject($result)); 
		die(); 
	}
	
	$arResult["FOLDERS"] = __WDGetSectionsTree($ob->real_path_full, $arParams["ACTIVE"]); 
/********************************************************************
				/Data
********************************************************************/

$popupWindow->ShowTitlebar(GetMessage("WD_TITLE"));
?>
<?
$popupWindow->StartContent();
?>
<script type="text/javascript">
function __wd_select_dir()
{
	this.timeout = {}; 
	__this_wd = this; 
}
__wd_select_dir.prototype.checkEvent = function(event, obj)
{
	if (event == "click") { obj.onclick = function() { return false; } };
	
	if (typeof obj != "object" || obj == null)
		return false;

	if (event == "dblclick") 
	{
		clearTimeout(this.timeout[obj.id]); 
		this.insertDir(obj); 
	} 
	else
	{ 
		this.timeout[obj.id] = setTimeout("__this_wd.checkDir('" + obj.title + "');", 200); 
	} 
	return false; 
}
__wd_select_dir.prototype.checkDir = function(obj_id)
{
	BX.ajax.post(
		"<?=$APPLICATION->GetCurPageParam("", array("active", "result"));?>", 
		{"return_array" : "Y", "active" : obj_id}, 
		function(data)
		{
			try
			{
				eval("var res = " + data + "; "); 
				res["dir_id"] = 'item_' + res["dir_id"]; 
				var parentAnchor = document.getElementById(res["dir_id"]);
				var parentNode = parentAnchor.parentNode; 
				for (var ii in res["subdirs"])
				{
					var text = '<div id="div_' + res["subdirs"][ii]["ID"] + '" class="folder-block"><a ' + 
						'onfocus="__wd_focus_blur(this, true);" ' + 
						'onblur="__wd_focus_blur(this, false);" ' + 
						'hidefocus="true" ' + 
						'id="item_' + res["subdirs"][ii]["ID"] + '" ' + 
						'class="folder-title-' + (res["subdirs"][ii]["HAS_DIR"] == true ? 'closed' : 'empty') + '" ' + 
						'href="#" ' + 
						'title="' + res["subdirs"][ii]["PATH"] + '" ' + 
						'onclick="' + (res["subdirs"][ii]["HAS_DIR"] ? '__wd_dir_selector.checkEvent(\'click\', this); ' : '' ) + 'return false;" ' + 
						'ondblclick="__wd_dir_selector.checkEvent(\'dblclick\', this);"><span><font>' + res["subdirs"][ii]["NAME"] + '</font></span></a></div>'; 

					parentNode.innerHTML += text; 
				}
				var parentAnchor = document.getElementById(res["dir_id"]);
				parentAnchor.onclick = function() {__wd_open_close(this); return false;};  
				parentAnchor.className = "folder-title-opened"; 
				__prev_focus = parentAnchor; 
				__wd_focus_blur(parentAnchor, true, true);
			}
			catch(e){}
		});
	
	return false;
}
__wd_select_dir.prototype.insertDir = function(obj)
{
	if (typeof obj != "object" || obj == null)
		return null; 
	var insertObj = document.getElementById("<?=$arParams["ELEMENT_ID"]?>");
	if (typeof insertObj != "object" || insertObj == null)
		return null; 
	var res = (obj.tagName == "A" ? obj.title : obj.value); 
	if (res)
		insertObj.value = res; 
	top.<?=$popupWindow->jsPopup?>.Close();
}
__wd_dir_selector = new __wd_select_dir(); 

function __wd_open_close(obj)
{
	if (typeof obj != "object" || obj == null)
		return null; 
	if (obj.className.indexOf("folder-title-opened") >= 0)
	{
		obj.parentNode.className += ' folder-block-closed'; 
		obj.className = obj.className.replace("folder-title-opened", "folder-title-closed"); 
	}
	else
	{
		obj.parentNode.className = obj.parentNode.className.replace("folder-block-closed", "").replace("  ", " "); 
		obj.className = obj.className.replace("folder-title-closed", "folder-title-opened"); 
	}
}
var __prev_focus = false; 
function __wd_focus_blur(obj, focus, set)
{
	if (typeof obj != "object" || obj == null)
		return null;

	
	if (focus == true)
	{
		if (__prev_focus)
		{
			__wd_focus_blur(__prev_focus, false);
			__prev_focus = false; 
		}

		if (set == true)
		{
			__prev_focus = obj; 
		}

		obj.className += ' folder-focused'; 
		document.getElementById('__wd_active_path').value = obj.title; 
	}
	else
	{
		obj.className = obj.className.replace(/folder\-focused/g, "").replace(/\s+/g, " "); 
	}
}
</script>
<?
$id = md5(time() . $GLOBALS["APPLICATION"]->GetServerUniqID());
?>
<div class="folder-blocks" id="__wd_div_blocks">
	<div id="div_<?=$id?>" class="folder-block folder-root-block">
		<a id="item_<?=$id?>" class="folder-root-title folder-title<? 
		if ($arParams["ACTIVE"] == "/" || empty($arParams["ACTIVE"])):
			?> folder-active<?
		endif;
		?>" href="#" <?
		?>hidefocus="true" title="/" <?
		?>ondblclick="__wd_dir_selector.checkEvent('dblclick', this);" <?
		?>onclick="return false;" <?
		?>onfocus="__wd_focus_blur(this, true);" onblur="__wd_focus_blur(this, false);" <?
		?>><span><font>/</font></span></a>
<?
		
	$arPath = array(); 
	$deep = 0; 
	$couner = $opened = $closed = 0; 
	$arParams["ACTIVE"] = str_replace("//", "/", "/".$arParams["ACTIVE"]."/"); 
	$activeNumber = 0;
	foreach ($arResult["FOLDERS"] as $res)
	{
		for ($ii = ($deep - $res["DEEP"]); $ii >= 0; $ii--):
			?></div><?
			$closed++; 
		endfor;
		
		$arPath = array_slice($arPath, 0, intval($res["DEEP"])); 
		$arPath[$res["DEEP"]] = $res["NAME"]; 
		$val = $name = str_replace("//", "/", "/".implode("/", $arPath)."/");
		$status = ($res["HAS_DIR"] != true ? "empty" : 
			((substr($arParams["ACTIVE"], 0, strlen($val)) == $val && strlen($val) <= strlen($arParams["ACTIVE"])) ? "opened" : "closed")); 
		$name = $res["NAME"]; 
		$id = md5($val);
		
	?><div id="div_<?=$id?>" class="folder-block"><?
		?><a id="item_<?=$id?>" <?
		?>hidefocus="true" <?
		?>onfocus="__wd_focus_blur(this, true);" <?
		?>onblur="__wd_focus_blur(this, false);" <?
		?>class="folder-title-<?=$status?><? 
		if ($arParams["ACTIVE"] == $val):
			?> folder-active<?
			$activeNumber = $couner; 
		endif;
		?>" href="#" title="<?=$val?>" onclick="<?=($status == "closed" ? 
			'__wd_dir_selector.checkEvent(\'click\', this); ' : 
			(
				$status == "opened" ? 
				'__wd_open_close(this); ' : 
				''
			))?>return false;" <?
		?> ondblclick="__wd_dir_selector.checkEvent('dblclick', this);"><span><font><?=$name?></font></span></a><?
		$opened++; 
		$deep = $res["DEEP"];
		$couner++; 
	}
	for ($ii = $deep; $ii > 0; $ii--):
		?></div><?
		$closed++;
	endfor;
?>
</div>
<input type="hidden" id="__wd_active_path" value="" />
<?
$height = 17; 
?>
<style> 
div.folder-blocks{}
div.folder-block {
	padding-left: 20px; 
	width: 90%; 
	height:auto;
	zoom: 1;
	display: block; }
div.folder-root-block{
	padding-left: 4px!important; }
div.folder-block-closed {
	height: <?=$height?>px;
	overflow: hidden!important; 
	zoom: 1;
	position: relative; }
div.folder-block a{
	outline: none;
	white-space: nowrap; 
	display: block; 
	position: relative; 
	cursor: default; 
	left: -17px;
	padding-left: 14px; 
	background-position: 0 50%; 
	background-repeat: no-repeat; 
	line-height: <?=$height?>px; 
	height: <?=$height?>px; 
	color: black; 
	text-decoration: none;}
div.folder-block a.folder-title-closed {
	background-image: url("/bitrix/images/main/file_dialog/icons/plus.gif"); }
div.folder-block a.folder-title-opened {
	background-image: url("/bitrix/images/main/file_dialog/icons/minus.gif"); }
div.folder-block a.folder-title-empty {
	background-image: url("/bitrix/images/main/file_dialog/icons/dot.gif"); }
div.folder-block a.folder-active {
	font-weight: bold; }
div.folder-block a span {
	padding-left: 18px!important; 
	background-image: url("/bitrix/images/main/file_dialog/icons/folder.gif"); 
	background-position: 0 50%; 
	background-repeat: no-repeat; }
div.folder-block a.folder-title-opened span {
	background-image: url("/bitrix/images/main/file_dialog/icons/folderopen.gif"); }
div.folder-block a span font {
	border: 1px solid white; 
	font-size: 11px;
	padding: 1px 1px; }
div.folder-block a.folder-focused span font {
	border: 1px solid #F0F0F0 !important; 
	background-color: #B6B6B8 !important; }
div.bx-core-dialog-foot input {
	width: 20%;}
</style>
<?

if ($activeNumber > 1)
{
?>
<script>
function __wd_set_scroll_top()
{
	var obj = document.getElementById('__wd_div_blocks'); 
	if (!obj)
		return false; 
	var parentNode =  obj;
	do
	{
		parentNode = parentNode.parentNode; 
	} while (parentNode && parentNode.parentNode && parseInt(parentNode.style.height ? parentNode.style.height.replace(/([^0-9]+)/gi, '') : 0) <= 0); 
	parentNode.scrollTop = <?=($activeNumber * $height)?>;
}
__wd_set_scroll_top(); 
</script>
<?
}
$popupWindow->EndContent();
if ($popupWindow->bButtonsStarted)
	$popupWindow->EndButtons();
?>
<script type="text/javascript"><?=$popupWindow->jsPopup?>.SetButtons(<?
	?>'<input type="button" value="<?=GetMessage("WD_SELECT")?>" onclick="__wd_dir_selector.insertDir(document.getElementById(\'__wd_active_path\'));" />');</script>
<script type="text/javascript"><?=$popupWindow->jsPopup?>.SetButtons([BX.WindowManager.Get().btnCancel]);</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
