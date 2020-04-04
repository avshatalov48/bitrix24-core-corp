<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
IncludeAJAX();
$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/js/main/utils.js"></script>', true);
/********************************************************************
				Input params
********************************************************************/
$arParams["USE_SEARCH"] = ($arParams["USE_SEARCH"] == "Y" && IsModuleInstalled("search") ? "Y" : "N");
$arParams["SHOW_WEBDAV"] = ($arParams["SHOW_WEBDAV"] == "N" ? "N" : "Y");
/********************************************************************
				/Input params
********************************************************************/
?>
<div class="wd-menu">
<table cellpadding="0" cellspacing="0" border="0" class="wd-menu">
	<thead><tr>
		<td class="left"><div class="empty"></div></td>
		<td class="center"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td></tr></thead>
	<tbody>
		<tr>
			<td class="left"><div class="empty"></div></td>
			<td class="center">
				<table cellpadding="0" cellspacing="0" border="0" class="wd-menu-inner">
					<tr>
						<td><div class="section-separator"></div></td>
<?
if (strpos($arParams["PAGE_NAME"], "WEBDAV_BIZPROC_WORKFLOW") !== false && 
	$arParams["USE_BIZPROC"] == "Y" && $arParams["PERMISSION"] >= "U" && IsModuleInstalled("bizprocdesigner")):
?>
						<td><div class="section-separator"></div></td>
						<td>
<div class="controls controls-view element-add element-add-bizproc-status">
	<a target="_self" href="<?=$arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"].
		(strpos($arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : "&")."init=statemachine"?>" <?
			?>title="<?=GetMessage("BPATT_HELP1_TEXT")?>">
		<?=GetMessage("BPATT_HELP1")?>
	</a>
</div>
						</td>
						<td class="separator"><div class="separator"></div></td>
						<td>
<div class="controls controls-view element-add element-add-bizproc-sequence">
	<a target="_self" href="<?=$arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"].
		(strpos($arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_EDIT"], "?") === false ? "?" : "").""?>" <?
		?>title="<?=GetMessage("BPATT_HELP2_TEXT")?>">
		<?=GetMessage("BPATT_HELP2")?>
	</a>
</div>
						</td>
<?
else:
if ($arParams["PAGE_NAME"] != "SECTIONS"):
?>
						<td>
<div class="controls controls-view sections">
	<a href="<?=$arResult["URL"]["SECTION"]["UP"]?>" title="<?=GetMessage("WD_GO_BACK_ALT")?>">
		<?=GetMessage("WD_GO_BACK")?>
	</a>
</div>
						</td>
						<td class="separator"><div class="separator"></div></td>
<?
endif;

if ($arParams["PERMISSION"] >= "U"):
	if ($arParams["PAGE_NAME"] == "SECTIONS"):
		if ($arParams["SHOW_WEBDAV"] == "Y"):
?>
						<td id="wd_create_in_ie" style="display:none;">
<div class="controls controls-action element-add">
	<a href="<?=$arResult["URL"]["ELEMENT"]["ADD"]?>" title="<?=GetMessage("WD_ELEMENT_ADD_ALT")?>" onclick="if(WDAddElement){WDAddElement(this);}return false;">
		<?=GetMessage("WD_ELEMENT_ADD")?>
	</a>
</div>
						</td>
						<td id="wd_create_in_ie_separator"  style="display:none;"><div class="separator"></div></td>
<?
		endif;
?>
						<td>
<div class="controls controls-action upload">
	<a target="_self" href="<?=$arResult["URL"]["ELEMENT"]["UPLOAD"]?>" title="<?=($arParams["SECTION_ID"] > 0 ?
		GetMessage("WD_UPLOAD_ALT") : GetMessage("WD_UPLOAD_ROOT_ALT"))?>" target="_self">
		<?=GetMessage("WD_UPLOAD")?>
	</a>
</div>
						</td>
<?
if ($arParams["SHOW_CREATE_LINK"] != "N"):
?>
						<td class="separator"><div class="separator"></div></td>
						<td>
<div class="controls controls-action add">
	<a href="<?=$arResult["URL"]["SECTION"]["ADD"]?>" title="<?=GetMessage("WD_SECTION_ADD_ALT")?>" onclick="if(WDAddSection){WDAddSection(this);}return false;">
		<?=GetMessage("WD_SECTION_ADD")?>
	</a>
</div>
						</td>
<?
endif;
?>
						<td class="separator"><div class="separator"></div></td>
<?
		if ($arParams["USE_SEARCH"] == "Y"):
?>
						<td>
<div class="controls controls-action search">
	<a href="<?=$arResult["URL"]["SECTION"]["ADD"]?>" title="<?=GetMessage("WD_SECTION_ADD_ALT")?>" onclick="if(WDAddSection){WDAddSection(this);}return false;">
		<?=GetMessage("WD_SECTION_ADD")?>
	</a>
</div>
						</td>
						<td class="separator"><div class="separator"></div></td>
<?
		endif;
	elseif (false && $arParams["PAGE_NAME"] == "ELEMENT"):
?>
						<td>
<div class="controls controls-action element_edit">
	<a href="<?=$arResult["URL"]["ELEMENT"]["EDIT"]?>" title="<?=GetMessage("WD_ELEMENT_EDIT_ALT")?>">
		<?=GetMessage("WD_ELEMENT_EDIT")?>
	</a>
</div>
						</td>
						<td class="separator"><div class="separator"></div></td>
						<td>
<div class="controls controls-action element_delete">
	<a href="<?=$arResult["URL"]["ELEMENT"]["DELETE"]?>" title="<?=GetMessage("WD_ELEMENT_DELETE_ALT")?>" <?
		?>onclick="return confirm('<?=CUtil::JSEscape(GetMessage("WD_ELEMENT_DELETE_CONFIRM"))?>');">
		<?=GetMessage("WD_ELEMENT_DELETE")?>
	</a>
</div>
						</td>
						<td class="separator"><div class="separator"></div></td>

<?
	endif;
endif;
if ($arResult["USER"]["SHOW"]["SUBSCRIBE"] == "Y" && strpos($arParams["PAGE_NAME"], "WEBDAV_BIZPROC_WORKFLOW") === false):
?>
						<td>
<?
if ($arResult["USER"]["SUBSCRIBE"]["FORUM"] == "Y"):
?>
<div class="controls controls-view unsubscribe">
	<a href="<?=$arResult["URL"]["UNSUBSCRIBE"]?>" title="<?=GetMessage("WD_UNSUBSCRIBE_FROM_FORUM")?>" <?
		?>onclick="return confirm('<?=CUtil::JSEscape(GetMessage("WD_SUBSCRIBE_DELETE_CONFIRM"))?>');">
		<?=GetMessage("WD_UNSUBSCRIBE")?>
	</a>
</div>
<?
else:
?>
<div class="controls controls-view subscribe">
	<a href="<?=$arResult["URL"]["SUBSCRIBE"]?>" title="<?=GetMessage("WD_SUBSCRIBE_TO_FORUM")?>" >
		<?=GetMessage("WD_SUBSCRIBE")?>
	</a>
</div>
<?
endif;
?>
						</td>
						<td class="separator"><div class="separator"></div></td>
<?
endif;
?>
						<td>
<div class="controls controls-view help">
	<a href="<?=$arResult["URL"]["HELP"]?>" title="<?=GetMessage("WD_HELP_ALT")?>" >
		<?=GetMessage("WD_HELP")?>
	</a>
</div>
						</td>
<?
if ($arParams["SHOW_WEBDAV"] == "Y"):
?>
						<td id="wd_map_in_ie_separator"  style="display:none;"><div class="separator"></div></td>
						<td id="wd_map_in_ie" style="display:none;">
<div class="controls controls-view maping">
	<a href="javascript:void(0);" onclick="WDMappingDrive('<?=CUtil::JSEscape(str_replace(":443", "", $arParams["BASE_URL"]))?>'); return false;" <?
		?>title="<?=GetMessage("WD_MAPING_ALT")?>"><?=GetMessage("WD_MAPING")?></a>
</div>
						</td>
<?
endif;
if ($arParams["USE_BIZPROC"] == "Y" && $arParams["PERMISSION"] > "U" && $arParams["CHECK_CREATOR"] != "Y" && 
	strpos($arParams["PAGE_NAME"], "WEBDAV_BIZPROC_WORKFLOW") === false):
?>
						<td><div class="separator"></div></td>
						<td>
<div class="controls controls-view bizproc">

		<a target="_self" href="<?=$arResult["URL"]["WEBDAV_BIZPROC_WORKFLOW_ADMIN"]?>"><?=GetMessage("WD_BP")?></a>
</div>
						</td>
<?
endif;
endif;



?>
					</tr>
				</table>
			</td>
			<td class="right"><div class="empty"></div></td></tr>
	</tbody>
	<tfoot><tr>
		<td class="left"><div class="empty"></div></td>
		<td class="center"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr></tfoot>
</table>
<?
if ($arParams["SHOW_WEBDAV"] == "Y"):
?>
<script>
if (document.attachEvent && navigator.userAgent.toLowerCase().indexOf('opera') == -1)
{
	if (document.getElementById('wd_create_in_ie'))
		document.getElementById('wd_create_in_ie').style.display = '';
	if (document.getElementById('wd_create_in_ie_separator'))
		document.getElementById('wd_create_in_ie_separator').style.display = '';
	if (document.getElementById('wd_map_in_ie'))
		document.getElementById('wd_map_in_ie').style.display = '';
	if (document.getElementById('wd_map_in_ie_separator'))
		document.getElementById('wd_map_in_ie_separator').style.display = '';
}
function WDMappingDrive(path)
{
	if (!jsUtils.IsIE())
	{
		return false;
	}
	if (!path || path.length <= 0)
	{
		alert('<?=GetMessage("WD_EMPTY_PATH")?>');
		return false;
	}

	var sizer = false;
	var text = '';
	var src = "";
	sizer = window.open("",'',"height=600,width=800,top=0,left=0");

	text = '<HTML><BODY>' +
			'<SPAN ID="oWebFolder" style="BEHAVIOR:url(#default#httpFolder)">' +
				'<?=CUtil::JSEscape(str_replace("#BASE_URL#", str_replace(":443", "", $arParams["BASE_URL"]), GetMessage("WD_HELP_TEXT")))?>' +
			'</SPAN>' +
		'<script>' +
			'var res = oWebFolder.navigate(\'' + path + '\');' +
		'<' + '/' + 'script' + '>' +
		'</BODY></HTML>';
	sizer.document.write(text);
}

if (typeof oText != "object")
	var oText = {};
oText['error_create_1'] = '<?=CUtil::JSEscape(GetMessage("WD_ERROR_1"))?>';
oText['error_create_2'] = '<?=CUtil::JSEscape(GetMessage("WD_ERROR_2"))?>';
</script>
<?
endif;
?>
</div>
