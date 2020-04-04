<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>
<?
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
if (isset($_REQUEST['AJAX']) || isset($_REQUEST['connect']))
{
	$APPLICATION->RestartBuffer();
	if (isset($_REQUEST['connect']))
	{
		$APPLICATION->IncludeComponent("bitrix:webdav.connector", ".default", Array(
			"BASE_URL"	=>	$arParams['BASE_URL'],
			"SET_TITLE"	=> "N",
		),
		$component,
		array("HIDE_ICONS" => "Y"));
	}
}
else 
{
	if (isset($_REQUEST['help']))
	{
		$APPLICATION->IncludeComponent( "bitrix:webdav.help", "", Array(), false);
		exit;
	} 
	else 
	{
?>
<table style="width:100%" cellpadding="0" cellspacing="0">
<tr><td style="vertical-align:top;">
<?
		CUtil::InitJSCore(array('ajax'));
		echo "<div id='wd_aggregator_tree'>\n";
	}
}
?>
<?
$curdepth = -1;
foreach ($arResult['STRUCTURE'] as $node)
{
	$depth = $node['DEPTH_LEVEL'];
	if ($depth > $curdepth) echo "<ul>\n";
	while ($depth < $curdepth--) echo "</ul>\n";
	if ($depth >= 0)
	{
		$link = rtrim($arParams['SEF_FOLDER'],'/') . '/index.php?target=' . $node['PATH'];

		if (isset($_REQUEST['clear_cache']))
			$link .= '&clear_cache=Y';

		$class = 'wd_aggregator_' . (isset($node['MODE'])?$node['MODE']:'remote');
		if (isset($node['CLASS']))
			$class .= " wd_ag_".$node['CLASS'];

		$annotation = '';
		$ibMode = 'WD_AG_DOCUMENTS';
		if (isset($node['IB_MODE']) && strlen($node['IB_MODE']) > 0)
			$ibMode = 'WD_AG_PUBLISHED_DOCUMENTS';

		if (isset($node['DOCCOUNT']) && $node['DOCCOUNT'] !== false)
			$annotation = GetMessage($ibMode, array("#N#" => intval($node['DOCCOUNT'])));

		if ($node['CLASS'] == 'personal' || $node['CLASS'] == 'workgroups')
			echo "<li class=\"wd_ag_delimiter\"><div id=\"pagetitle-underline\"></div></li>\n";

		echo "<li class=\"".$class."\"><a href=\"".$link."\">".htmlspecialcharsbx($node['NAME'])."</a><span>".$annotation."</span></li>\n";
	}
	$curdepth = $depth;
}
while (0 <= $curdepth--) echo "</ul>\n";
?>

<?
if (!isset($_REQUEST['AJAX']))
{

// set title buttons
$this->SetViewTarget("pagetitle", 100);
?>
<div id='wd-aggregator-buttons'>
<?
if ($GLOBALS['USER']->IsAdmin())
{
	$sNewFolderPath = $APPLICATION->GetCurPage(false);

	while (!is_dir( str_replace(array("///", "//"), "/", $_SERVER['DOCUMENT_ROOT'] . $sNewFolderPath) ))
	{
		$sNewFolderPath = implode('/', array_slice(explode('/', $sNewFolderPath), 0, -1));
	}
	if (strlen($sNewFolderPath)>0 && substr($sNewFolderPath, -1, 1) !== '/') $sNewFolderPath .= '/';
	

	$urlCreateLibrary = $APPLICATION->GetPopupLink(array('URL' => WDAddPageParams('/bitrix/admin/public_file_new.php', array(
		'wiz_template' => 'library',
		'lang' => LANGUAGE_ID,
		'site' => LANG,
		//'mode'=>'iblock',
		'newFolder' => 'Y',
		'path' => $sNewFolderPath,
		'back_url' => $APPLICATION->GetCurPage()
		))));
	//$urlCreateStorage = $APPLICATION->GetPopupLink(array('URL' => WDAddPageParams('/bitrix/admin/public_file_new.php', array(
		//'wiz_template'=>'library',
		//'lang'=>LANGUAGE_ID,
		//'site'=>LANG,
		//'mode'=>'folder',
		//'newFolder'=>'Y',
		//'path'=>$APPLICATION->GetCurPage(false),
		//'back_url'=>$APPLICATION->GetCurPage()
		//))));
?><a href="#" onclick="javascript:<?=$urlCreateLibrary?>" class="button-add"><span class="icon"></span><?=GetMessage('WD_AG_ADD_LIBRARY')?></a><?
}
	$urlConnector = $APPLICATION->GetPopupLink(array('URL' => $APPLICATION->GetCurPageParam("connect=Y")));
?><a href="#" onclick="javascript:<?=$urlConnector?>" class="button-connect"><?=GetMessage('WD_AG_MAP_DRIVE')?></a></div>
<?
$this->EndViewTarget();
?>
</div>

</td>
<?	$url = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$arParams['SEF_FOLDER']; ?>
<br />
</tr>
</table>

<script language='JavaScript'>
	function wd_dl_parseDoclistUl(ul)
	{
		if (!ul) return {};
		var rows = new Array();
		var li = BX.findChild(ul, {tag:'li'}, false);
		while (li != null)
		{
			rows.push(li);
			var subul = BX.findChild(li, {tag:'ul'}, false);
			if (subul)
			{
				rows.concat(parseDoclistUl(subul));
			}
			li = BX.findNextSibling(li, {tag:'li'}, false);
		}
		return rows;
	}

	function wd_dl_loadNode(result, target, show, reset)
	{
		if ((window.wd_ag_show != null) && (window.wd_ag_show != true))
			BX.closeWait(target, window.wd_ag_show);
		window.wd_ag_show = false;

		target = BX('wd_aggregator_tree');
		var tmp = BX('aggregator_temp');
		var nodeButtons = BX('wd-aggregator-buttons');
		if (null == tmp)
		{
			tmp = BX.create('div', {props:{'id':'tmp'}, style:{'display':'none'}}, document);
		}
		tmp.innerHTML = result;
		var parentUl = BX.findChild(tmp, {tag:'ul'}, false);
		var childUl = BX.findChild(parentUl, {tag:'ul'}, true);
		var link = BX.findChild(target, {tag:'a'}, false);
		BX.unbindAll(link);
		if (reset != null) // load root tree
		{
			var backLink = BX.findChild(nodeButtons, {'class': 'button-back'});
			if (null != backLink) BX.remove(backLink);
			BX.remove(target.children[0]);
			target.appendChild(parentUl);
			if (window.pagetitle)
				BX('pagetitle').childNodes[0].data = window.pagetitle;
			wd_dl_initTreeEvents(parentUl);
		}
		else if (childUl != null)
		{
			var titleLi = BX.findChild(parentUl, {'class':'wd_aggregator_local'});
			var title = titleLi.children[0].innerHTML;

			var pagetitle = BX('pagetitle');
			if (pagetitle)
			{
				window.pagetitle = pagetitle.childNodes[0].data;
				pagetitle.childNodes[0].data = title;
			}

			BX.remove(target.children[0]);
			target.appendChild(childUl);
		} else {
			var titleLi = BX.findChild(parentUl, {'class':'wd_aggregator_local'});
			var title = titleLi.children[0].innerHTML;
			window.pagetitle = BX('pagetitle').childNodes[0].data;
			BX('pagetitle').childNodes[0].data = title;

			emptyUl = BX.create('ul');
			emptyLi = BX.create('li', {attrs:{'class':'wd_ag_empty'}});
			emptyLi.innerHTML = "<?=GetMessage('WD_NO_LIBRARIES');?>";
			emptyUl.appendChild(emptyLi);

			BX.remove(target.children[0]);
			target.appendChild(emptyUl);
		}
		var backLink = BX.findChild(nodeButtons, {'class': 'button-back'});
		if (null == backLink && null == reset)
		{
			nodeBackLink = BX.create('a', {attrs:{'class': 'button-back', 'href': '#'}});
			nodeBackLink.innerHTML = "<span class=\"icon\"></span><?=CUtil::JSEscape(GetMessage("WD_AG_GO_BACK"))?>";
			BX.bind(nodeBackLink, 'click', function(e) {wd_dl_reload(); BX.PreventDefault(e);});
			nodeButtons.insertBefore(nodeBackLink, nodeButtons.children[0]);
		}
		BX.remove(tmp);
	}

	function wd_showwait()
	{
		window.wd_ag_show = true;
		setTimeout(function() {
			if (window.wd_ag_show == true)
				window.wd_ag_show = BX.showWait(BX('wd_aggregator_tree'));
		}, 500);
	}

	function wd_dl_reload()
	{
		wd_showwait();
		BX.ajax.get('<?=CUtil::JSEscape(POST_FORM_ACTION_URI);?>?AJAX=Y', null, function(result) {wd_dl_loadNode(result, document, false, true);});
	}

	function wd_dl_collapseNode(tthis)
	{
		var ul = BX.findChild(tthis.parentNode, {tag:'ul'}, false);
		BX.remove(ul);
		BX.unbindAll(tthis);
		BX.addClass(BX.removeClass(tthis.parentNode, 'wd_aggregator_expanded'), 'wd_aggregator_local');
		BX.bind(tthis, 'click', function(e){wd_dl_expandNode(this); return BX.PreventDefault(e);});
	}

	function wd_dl_expandNode(tthis)
	{
		var href = tthis.href;
		href += ((href.indexOf('?') > 0) ? '&' : '?');
		href += 'AJAX=Y';
		wd_showwait();
		BX.unbindAll(tthis);
		BX.bind(tthis, 'click', function(e){return BX.PreventDefault(e);});
		BX.ajax.get(href, null, function(result) {wd_dl_loadNode(result, tthis.parentNode, false);});
	}

	function wd_dl_initTreeEvents(node)
	{
		var lis = wd_dl_parseDoclistUl(node);
		for (var i=0;i<lis.length;i++)
		{
			var li = lis[i];
			var link = BX.findChild(li, {tag:'a'}, false);
			if (li.className.indexOf('wd_aggregator_local') != -1) 
			{
				BX.bind(link, 'click', function(e){wd_dl_expandNode(this); return BX.PreventDefault(e);});
			}
		}
	}

BX.ready(function() {
	var div = document.getElementById('wd_aggregator_tree');
	var ul = BX.findChild(div, {tag:'ul'}, true);
	wd_dl_initTreeEvents(ul);
});
</script>
<?
} else {
	die();
}
?>
