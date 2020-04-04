<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS['APPLICATION']->RestartBuffer();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/template.php")));
__IncludeLang($file);

if (isset($_REQUEST['CHECK_NAME']))
{
	$fileName = CUtil::ConvertToLangCharset(urldecode($_REQUEST['CHECK_NAME']));
	$ob = $arParams['OBJECT'];
	$fileName = $ob->CorrectName($fileName);
	$result = array();
	$result['name'] = $fileName;
	$result['section'] = $arParams['SECTION_ID'];
	if (!check_bitrix_sessid())
	{
		$result['permission'] = false;
		$result['errormsg'] = GetMessage("WD_ERROR_BAD_SESSID");
	}
	elseif (!$ob->CheckWebRights("PUT", array('arElement' => $fileName)))
	{
		$result['permission'] = false;
		$oError = $GLOBALS["APPLICATION"]->GetException();
		$result['errormsg'] = ($oError ? $oError->GetString() : GetMessage("WD_ERROR_UPLOAD_BAD_FILE"));
	}
	elseif (COption::GetOptionString('webdav', 'hide_system_files', 'Y') == 'Y' && substr($fileName, 0, 1) == '.')
	{
		$result['permission'] = true;
		$result['okmsg'] = GetMessage("WD_WARNING_FIRST_DOT");
	}
	elseif (
		is_numeric($arParams['ELEMENT_ID'])
		&& ($arParams['ELEMENT_ID'] == 0)
		&& (!$ob->CheckUniqueName($fileName, $arParams["SECTION_ID"], $res))
	)
	{
		if ($ob->Type == "folder")
		{
			$result['permission'] = false;
			$result['errormsg'] = GetMessage("WD_ERROR_SAME_NAME");
		}
		else
		{
			$result['permission'] = true;
			$result['okmsg'] = GetMessage("WD_WARNING_SAME_NAME", array("#LINK#"=>'class="ajax" onclick="WDUploadExpand();"'));
		}
	}
	elseif (($arParams['ELEMENT_ID'] !== 0) && ($arResult['ELEMENT']['FILE_EXTENTION'] != strToLower(strrchr($fileName, "."))))  
	{
		$result['permission'] = false;
		$result['errormsg'] = GetMessage("WD_WARNING_EXTENSIONS_DONT_MATCH");
	}
	else 
	{
		$result['permission'] = true;
		$result['okmsg'] = '';
	}
	echo CUtil::PhpToJSObject($result);
	die();
}

$popupWindow = new CJSPopup('', '');
if ($arParams['ELEMENT_ID'] != 0) 
{
	$popupWindow->ShowTitlebar(TruncateText(GetMessage("WD_UPLOAD_VERSION_TITLE", array("#NAME#" => $arResult["ELEMENT"]["NAME"])), 75));
} else {
	$popupWindow->ShowTitlebar(GetMessage("WD_UPLOAD_TITLE"));
}
$popupWindow->StartContent();
?>
</form>
<table id="wd_messages" style="display:none;" cellpadding="0" cellspacing="0" width="100%"> <tr><td>
	<div id="wd_upload_error_message" style="color:#dd0000;"></div>
	<div id="wd_upload_ok_message" style="color:#009900;"></div>
</td></tr></table>
<iframe id="upload_iframe" name="upload_iframe" style="display:none;"> </iframe>
<form method="post" name='wd_upload_form' id="wd_upload_form" target="upload_iframe" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
<?=bitrix_sessid_post()?>
<input type="hidden" name="SECTION_ID" value="<?=htmlspecialcharsbx($arParams["SECTION_ID"])?>" />
<input type="hidden" name="ELEMENT_ID" value="<?=htmlspecialcharsbx($arParams["ELEMENT_ID"])?>" />
<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=intval($arResult["SECTION"]["IBLOCK_SECTION_ID"])?>" />
<input type="hidden" name="edit_section" value="Y" />
<input type="hidden" name="save_upload" value="Y" />
<input type="hidden" name="ACTIVE" value="Y" />
<input type="hidden" name="overview" value="Y" />
<input type="hidden" name="AJAX_CALL" value="Y" />
<input type="hidden" name="SIMPLE_UPLOAD" value="Y" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?=htmlspecialcharsbx($arParams["UPLOAD_MAX_FILESIZE_BYTE"])?>" />
<? if ($arParams['ELEMENT_ID'] != 0): ?>
<input type="hidden" name="overview" value="Y" />
<? endif; ?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style='vertical-align:middle;'>
			<span class="required starrequired">*</span><?=GetMessage("File")?>:
		</td>
		<td style='vertical-align:top;'>
			<span class="webform-field-upload-list">
				<input type="file" id="SourceFile_1" name="SourceFile_1" style="width:90%;" />
			</span>
			<p class='drop-note' style='display: none;'>
				<?=GetMessage("WD_UPLOAD_DROPHERE")?>
			</p>
		</td>
	</tr>
<? if (($arParams['ELEMENT_ID'] != 0) && ($arResult["ELEMENT"]["LOCK_STATUS"] == 'yellow')): ?>
	<tr>
		<td width="40%">
			<?=GetMessage("WD_UPLOAD_UNLOCK")?>:
		</td>
		<td width="60%">
			<input type="checkbox" id="wd_upload_unlock" name="UploadUnlock" style="width:90%;" />
		</td>
	</tr>
<? endif; ?>
</table>
<? if (($arParams['ELEMENT_ID'] == 0) && ($arParams["OBJECT"]->Type=='iblock')): ?>
<p><?=GetMessage('WD_UPLOAD_EXPAND_PROPS', array("#LINK#" => 'id="wd_upload_expand" onclick="WDUploadExpand(this);"'));?></p>
<? endif; ?>
<table id="wd_upload_props" style="display:none;" cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="40%">
			<?=GetMessage("Title")?>:
		</td>
		<td width="60%">
<?if ($arParams["OBJECT"]->Type=='iblock'):?>
			<input type="text" id="Title_1" name="Title_1" style="width:90%;" value="<?=(isset($arResult['ELEMENT']['NAME']) ? htmlspecialcharsbx($arResult['ELEMENT']['NAME']) : '')?>" />
<?else:?>
			<input type="text" id="Title_1" name="Title_1" style="width:90%;" value="<?=(isset($arParams['SECTION_ID']) ? htmlspecialcharsbx($arParams['OBJECT']->arParams['base_name']) : '')?>" />
<?endif;?>
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?=GetMessage("Tags")?>:
		</td>
		<td width="60%">
<?
	if (IsModuleInstalled("search"))
	{
?>
		<script>
		BX( function() {
			setTimeout( function() {
				TCJsUtils._show = TCJsUtils.show;
				TCJsUtils.show = function(oDiv, iLeft, iTop) {
					oDiv.style.zIndex = 3000;
					return TCJsUtils._show(oDiv, iLeft, iTop);
				}
			}, 800); });
		</script>
<?
		$arTagParams = array(
			"VALUE" => (isset($arResult['ELEMENT']['TAGS']) ? $arResult['ELEMENT']['TAGS'] : ''), 
			"NAME" => "Tag_1",
			"ID" => "Tag_1"
		);
		if (isset($ob->attributes['group_id']))
		{
			$groupID = intval($ob->attributes['group_id']);
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
	}
	else
	{
?>
		<input type="text" id="Tag_1" name="Tag_1" style="width:90%;" value="<?=(isset($arResult['ELEMENT']['TAGS']) ? $arResult['ELEMENT']['TAGS'] : '')?>" />
<?
	}
?>
		</td>
	</tr>
	<tr>
		<td width="40%">
			<?=GetMessage("Description")?>:
		</td>
		<td width="60%">
<?
		if(CModule::IncludeModule("fileman"))
		{
			$ar = array(
				'width' => '96%',
				'height' => '200',
				'inputName' => 'Description_1',
				'inputId' => 'Description_1',
				'jsObjName' => 'pLEditorDav',
				'content' => (isset($arResult['ELEMENT']['~PREVIEW_TEXT']) ? trim($arResult['ELEMENT']['~PREVIEW_TEXT']) : ''),
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
?>
		</td>
	</tr>
</table>
<? include trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/footer.php"))); ?>
<? if (($arParams["OBJECT"]->Type == "iblock" && $arParams["ELEMENT_ID"] == 0) || ($arParams["OBJECT"]->Type == "folder" && $arParams["OBJECT"]->arParams['is_file'] == false)): ?>
<p><?=GetMessage('WD_UPLOAD_EXTENDED', array("#LINK#" => htmlspecialcharsbx($APPLICATION->GetCurPage() . '?ncc=1')));?></p>
<? endif; ?>
<script>

function WDUploadExpand(link)
{
	var wdExtTable = BX('wd_upload_props');
	if (wdExtTable.style.display != 'table')
	{
		wdExtTable.style.display = 'table';
		if (link)
			link.parentNode.innerHTML = '<?=GetMessage('WD_UPLOAD_COLLAPSE_PROPS', array("#LINK#" => 'id="wd_upload_expand" onclick="WDUploadExpand(this);"'))?>';
	}
	else
	{
		wdExtTable.style.display = 'none';
		if (link)
			link.parentNode.innerHTML = '<?=GetMessage('WD_UPLOAD_EXPAND_PROPS', array("#LINK#" => 'id="wd_upload_expand" onclick="WDUploadExpand(this);"'))?>';
	}
}

function WDUploadInit(dialog)
{
	dialog.WDUploadInit({
		'msg':{
			'Submit':"<?=CUtil::JSEscape(GetMessage((($arParams['ELEMENT_ID'] == 0)?'Send':'Send_Version')));?>",
			'Close':"<?=CUtil::JSEscape(GetMessage('WD_CLOSE'));?>",
			'SendVersion':"<?=CUtil::JSEscape(GetMessage("Send_Version"))?>",
			'SendDocument':"<?=CUtil::JSEscape(GetMessage("Send_Document"))?>",
			'UploadSuccess':"<?=CUtil::JSEscape(GetMessage('WD_UPLOAD_SUCCESS'));?>",
			'UploadInterrupt':"<?=CUtil::JSEscape(GetMessage('WD_UPLOAD_INTERRUPT_BEWARE'))?>",
			'UploadInterruptConfirm':"<?=CUtil::JSEscape(GetMessage('WD_UPLOAD_INTERRUPT_CONFIRM'))?>",
			'UploadNotDone':"<?=CUtil::JSEscape(GetMessage('WD_UPLOAD_NOT_DONE'))?>",
			'UploadNotDoneAsk':"<?=CUtil::JSEscape(GetMessage('WD_UPLOAD_NOT_DONE_ASK'))?>"
		},
		'fileUpdate':<?=(($arParams['ELEMENT_ID'] != 0) ? "true" : "false" )?>,
		'closeAfterUpload': <?=((isset($_REQUEST["close_after_upload"]) && $_REQUEST["close_after_upload"] == "Y") ? "true" : "false")?>,
		'dropAutoUpload': <?=((isset($_REQUEST["bp_param_required"])) ? "false" : "true")?>,
		'checkFileUrl': "<?=CUtil::JSEscape(POST_FORM_ACTION_URI);?>",
		'uploadFileUrl': "<?=CUtil::JSEscape(WDAddPageParams(POST_FORM_ACTION_URI, array('use_light_view'=>'Y')));?>",
		'targetUrl': "<?=CUtil::JSEscape($url)?>",
		'updateDocument': <?=(isset($_REQUEST["update_document"]) ? "true" : "false" )?>,
		'sessid':"<?=bitrix_sessid()?>",
		'sectionID':"<?=intval($arParams['SECTION_ID'])?>",
		'elementID':"<?=CUtil::JSEscape(urlencode($arParams['ELEMENT_ID']))?>"
	});
	BX.onCustomEvent(dialog,  'onUploadPopupReady');
}

BX(function() {
	var dialog = BX.WindowManager.Get();
	BX.loadScript("<?=CUtil::GetAdditionalFileURL($this->__folder . '/script_deferred.js')?>", function(){
		WDUploadInit(dialog);
	});
});
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
