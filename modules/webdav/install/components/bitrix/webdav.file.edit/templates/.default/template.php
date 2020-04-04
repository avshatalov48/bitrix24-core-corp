<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/webdav/templates/.default/script.js");
endif;
CUtil::InitJSCore(array('window', 'ajax'));

$aCols = __build_item_info($arResult["ELEMENT"], ($arParams + array("TEMPLATES" => array()))); 
$aCols = $aCols['columns'];

if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;
if (!empty($arResult["NOTIFY_MESSAGE"])):
	ShowNote($arResult["NOTIFY_MESSAGE"]);
endif;

	$addDownload = '<span class="wd-item-controls element_download" style="float:none; "><a target="_blank" href="'.$arResult["ELEMENT"]["URL"]["THIS"].'">'.GetMessage("WD_DOWNLOAD_FILE").'</a></span>';
	$addFileSize = "<span class=\"wd-file-size\">(".$arResult["ELEMENT"]["FILE_SIZE"].")</span>";
	$arFields[] = array("id" => "NAME", "name" => GetMessage("WD_FILE"), "type" => "custom", "value" => 
		"<div class=\"quick-view wd-toggle-edit wd-file-name\">".$aCols["NAME"].$addFileSize.$addDownload."</div><input class=\"quick-edit wd-file-name\" type=\"text\" name=\"NAME\" value=\"".$arResult["ELEMENT"]["NAME"]."\"/>".$addButtons);
	$arFields[] =  array("id" => "MODIFIED", "name" => GetMessage("WD_FILE_MODIFIED"), "type" => "label", "value" => "<div style='margin-left:8px;'>".FormatDateFromDB($arResult["ELEMENT"]["TIMESTAMP_X"])."</div>");

if ($this->__component->__parent)
{
	$this->__component->__parent->arResult["arButtons"] = (is_array($this->__component->__parent->arResult["arButtons"]) ? 
		$this->__component->__parent->arResult["arButtons"] : array()); 

	$this->__component->__parent->arResult["arButtons"]["copy_link"] = array(
		"TEXT" => GetMessage("WD_COPY_LINK"),
		"TITLE" => GetMessage("WD_COPY_LINK_TITLE"),
		"LINK" => "javascript:WDCopyLinkDialog('".CUtil::JSEscape($GLOBALS['APPLICATION']->IsHTTPS() ? 'https' : 'http').'://'.str_replace("//", "/", $_SERVER['HTTP_HOST']."/".$arResult["ELEMENT"]["URL"]["THIS"])."')",
		"ICON" => "btn-copy element-copy"
	); 
	if ($arResult["WRITEABLE"] == "Y" && $arResult["ELEMENT"]["SHOW"]["DELETE"] == "Y")
	{
		$this->__component->__parent->arResult["arButtons"][] = array("NEWBAR" => true); 

		$this->__component->__parent->arResult["arButtons"]["delete"] = array(
			"TEXT" => GetMessage("WD_DELETE_FILE"),
			"TITLE" => GetMessage("WD_DELETE_FILE_ALT"),
			"LINK" => "javascript:WDDrop('".CUtil::JSEscape($arResult["ELEMENT"]["URL"]["DELETE"])."');",
			"ICON" => "btn-delete element-delete"); 
	}
}
if ($arResult["WRITEABLE"] == "Y")
{
	$arFields[] = array("id" => "BUTTONS2", "name" => "", "type" => "custom", "colspan" => true, "value" => "
		<table width=\"100%\"><tr><td style=\"width:30.5%; background-image:none;\"></td><td style=\"background-image:none;\">".
		'<input type="button" class="button-view" style="margin-right:10px; float: left; " id="wd_end_edit" value="'.htmlspecialcharsbx(GetMessage("WD_END_EDIT")).'" />'.
		'<input type="button" class="button-view" style="display: none; margin-right:10px; float: left; " id="wd_edit_office" value="'.htmlspecialcharsbx(GetMessage("WD_EDIT_MSOFFICE")).'" />'.
		'<input type="button" class="button-edit" style="display: none; margin-right:10px; float: left;" id="wd_commit" value="'.htmlspecialcharsbx(GetMessage("WD_SAVE")).'" />'.
		'<input type="button" class="button-edit" style="display: none; margin-right:10px; float: left;" id="wd_rollback" value="'.htmlspecialcharsbx(GetMessage("WD_CANCEL")).'" />'.
		'<input type="hidden" name="ELEMENT_ID" value="'.$arResult["ELEMENT"]["ID"].'" />'.
		'<input type="hidden" name="edit" value="Y" />'.
		'<input type="hidden" name="ACTION" value="EDIT" />'.
		'</td></tr></table>'
	);
}

?><?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID" => $arParams["FORM_ID"],
		"TABS" => array(
			array(
				"id" => "tab1", 
				"name" => GetMessage("WD_DOCUMENT"), 
				"title" => GetMessage("WD_DOCUMENT_ALT"), 
				"fields" => $arFields
		)),
		"BUTTONS" => array(
			"back_url" => "", 
			"standard_buttons" => false,
			//"custom_html" => 
		)
	),
	($this->__component->__parent ? $this->__component->__parent : $component)
);

$arOfficeExtensions = __wd_get_office_extensions();
?>

<script>
var viewElements;
var editElements;

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

	for (var i in localViewElements)
		localViewElements[i].style.display = off;

	for (var i in localEditElements)
		localEditElements[i].style.display = on;

	var on	= (buttons ? 'block' : 'none');
	var off = (buttons ? 'none' : 'block');
	for (var i in viewButtons)
		viewButtons[i].style.display = off;

	for (var i in editButtons)
		editButtons[i].style.display = on;
}

var WDCommit = function()
{
	editMode = false;
	BX('form_webdav_file_edit').submit();
}

var WDRollback = function()
{
	window.location.reload(true);
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
	BX.ajax.get("<?=CUtil::JSEscape(
						WDAddPageParams(
							$arResult["ELEMENT"]["URL"]["UPLOAD"],
							array(
								"use_light_view" => "Y",
								"close_after_upload" => "Y",
								"update_document" => urlencode($arParams["ELEMENT_ID"])
							), false
						)
				)?>", null, function(data) {
		BX.closeWait(null, wait);
		uploadDialog = new BX.CDialog({"content": data || '&nbsp', "width":650 , "height":150 });
		uploadDialog.WDUploaded = false;
		uploadDialog.WDUpdate = true;
		editMode = false;
		BX.addCustomEvent(uploadDialog, 'onBeforeWindowClose', function() {
			if (!(uploadDialog.WDUploaded))
			{
				editMode = true;
			}
		});
		uploadDialog.Show();
	});
}

var WDRename = function()
{
	var nameField = BX.findChild(document, {'class':'wd-file-name'}, true);
	WDActivateEdit(nameField);
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
		inputField.focus();
	}
}

function WDActivateQuickEdit(elm)
{
	var aHrefs = BX.findChild(elm, {'tag': 'a'}, true, true);
	for (j in aHrefs)
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

function WDCopyLinkDialog(url)
{
	var wdc = new BX.CDialog({'title': '<?=CUtil::JSEscape(GetMessage('WD_COPY_LINK_TITLE'));?>', 'content':"<form><input type=\"text\" readonly=\"readonly\" style=\"width:482px\"><br /><p><?=CUtil::JSEscape(GetMessage("WD_COPY_LINK_HELP"));?></p></form>", 'width':520, 'height':120});

	wdc.SetButtons("<input type=\"button\" onClick=\"BX.WindowManager.Get().Close()\" value=\"<?=CUtil::JSEscape(GetMessage('MAIN_CLOSE'))?>\">");
	wdc.Show();

	var wdci = BX.findChild(wdc.GetForm(), {'tag':'input'})
	wdci.value = url.replace(/ /g, "%20");
	wdci.select();
}

<? if ($arResult["WRITEABLE"] == "Y") { ?>
BX(function() {
	var viewRoot = BX('form_webdav_file_edit');
	docID = BX.findChild(viewRoot, {'attribute':{'name':'ELEMENT_ID'}}, true).value;
	viewElements = BX.findChild(viewRoot, {'class': 'quick-view'}, true, true);
	editElements = BX.findChild(viewRoot, {'class': 'quick-edit'}, true, true);
	viewButtons = BX.findChild(viewRoot, {'class': 'button-view'}, true, true);
	editButtons = BX.findChild(viewRoot, {'class': 'button-edit'}, true, true);
	WDChangeMode(false, false);
	BX.bind(BX('wd_end_edit'), 'click', function() { WDFileUpload()});
	BX.bind(BX('wd_rollback'), 'click', WDRollback);
	BX.bind(BX('wd_commit'), 'click', WDCommit);
	BX.bind(BX.findChild(viewRoot, {'tag':'input', 'class':'wd-file-name'}, true), 'keypress', WDEnterSubmit);

	// hover hilight and edit
	var aElements = BX.findChild(viewRoot, {'class':'wd-toggle-edit'}, true, true);
	for (i in aElements)
	{
		if (! BX.hasClass(aElements[i], 'no-quickedit'))
		{
			WDActivateQuickEdit(aElements[i]);
		}
	}

	if (BX.findChild(viewRoot, {'class': 'element-status-yellow'}, true))
	{
		downloadDone = true;
		WDChangeMode(false, true);
	} else {
		WDChangeMode(false, false);
	}
	if (window.location.href.indexOf("#upload") > -1) 
	{
		WDFileUpload();
	} 
	else if (window.location.href.indexOf('#edit') > -1)
	{
		WDEditDocument("<?=CUtil::JSEscape($arResult["ELEMENT"]["URL"]["DOWNLOAD"])?>");
	}

<? if (in_array($arResult["ELEMENT"]["EXTENTION"], $arOfficeExtensions)) { ?>
	var btn_edit_office = BX('wd_edit_office');
	if (WDCheckOfficeEdit())
	{
		var officetitle = WDEditOfficeTitle();
		btn_edit_office.style.display = 'block';
		if (officetitle != false)
			btn_edit_office.value = officetitle;

		BX.bind(BX('wd_edit_office'), 'click', function() {
			EditDocWithProgID('<?=CUtil::addslashes($arResult["ELEMENT"]["URL"]["~THIS"])?>')
		});
	} else {
		BX.remove(BX('wd_edit_office'));
	}
<? } else { ?>
	BX.remove(BX('wd_edit_office'));
<? } ?>
});
<? } ?>

</script>
