<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/template.php")));
__IncludeLang($file);

$GLOBALS['APPLICATION']->RestartBuffer();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$UCID = RandString(8);

if (isset($_REQUEST['CHECK_NAME']))
{
	$fileName = ($_REQUEST['CHECK_NAME']);
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
	// don't check for unique file names
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

?>
<script type="text/javascript" src="<?=CUtil::GetAdditionalFileURL('/bitrix/js/main/core/core_dd.js')?>"></script>
<table id="wd_messages<?=$UCID?>" style="display:none;" cellpadding="0" cellspacing="0" width="100%"> <tr><td>
	<div id="wd_upload_error_message<?=$UCID?>" style="color:#dd0000;"></div>
	<div id="wd_upload_ok_message<?=$UCID?>" style="color:#009900;"></div>
</td></tr></table>
<iframe id="upload_iframe<?=$UCID?>" name="upload_iframe<?=$UCID?>" style="display:none;"> </iframe>
<form method="post" id="wd_upload_form<?=$UCID?>" target="upload_iframe<?=$UCID?>" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="SECTION_ID" value="<?=htmlspecialcharsbx($arParams["SECTION_ID"])?>" />
	<input type="hidden" name="ELEMENT_ID" value="<?=htmlspecialcharsbx($arParams["ELEMENT_ID"])?>" />
	<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=intval($arResult["SECTION"]["IBLOCK_SECTION_ID"])?>" />
	<input type="hidden" name="edit_section" value="Y" />
	<input type="hidden" name="save_upload" value="Y" />
	<input type="hidden" name="use_light_view" value="Y" />
	<input type="hidden" name="ACTIVE" value="Y" />
	<input type="hidden" name="overview" value="Y" />
<? if (isset($_REQUEST['random_folder'])) { ?>
	<input type="hidden" name="random_folder" value="Y" />
<? } else if (isset($_REQUEST['dropped'])) { ?>
	<input type="hidden" name="dropped" value="Y" />
<? } ?>
	<input type="hidden" name="AJAX_CALL" value="Y" />
	<input type="hidden" name="SIMPLE_UPLOAD" value="Y" />
	<input type="hidden" name="MAX_FILE_SIZE" value="<?=htmlspecialcharsbx($arParams["UPLOAD_MAX_FILESIZE_BYTE"])?>" />

	<span class="webform-field-upload-list">
		<input type="file" id="SourceFile_1<?=$UCID?>" name="SourceFile_1" style="width:90%;" multiple="true" />
		<input type="text" id="Title_1<?=$UCID?>" name="Title_1" value="" />
	</span>
</form>
<script>
<?
$deferedName = str_replace("template.php", "script_deferred.js", __FILE__);
$mtime = 0;
if (file_exists($deferedName))
	$mtime = filemtime($deferedName);
?>
BX(function() {
	BX.loadScript("<?=$this->__folder . '/script_deferred.js?'.$mtime?>", function(){
		var dialog = new BX.WDFileHiddenUpload();
		dialog.UploadInit({
			'ucid':"<?=$UCID?>",
			'parentID':<?=(isset($_REQUEST['parentID']) ? intval($_REQUEST['parentID']) : 0)?>,
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
			'dropAutoUpload': "true",
			'checkFileUrl': "<?=CUtil::JSEscape(htmlspecialcharsback(POST_FORM_ACTION_URI));?>",
			'uploadFileUrl': "<?=CUtil::JSEscape(htmlspecialcharsback(POST_FORM_ACTION_URI));?>",
			'targetUrl': "<?=CUtil::JSEscape($url)?>",
			'sessid':"<?=bitrix_sessid()?>"
		});
	});
});
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
