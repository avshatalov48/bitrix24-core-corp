<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS['APPLICATION']->RestartBuffer();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/template.php")));
__IncludeLang($file);

if (!empty($arResult["ERROR_MESSAGE"]))
{
	echo CUtil::PhpToJSObject(array('result' => 'error', 'errmsg' => strip_tags($arResult["ERROR_MESSAGE"])));
	die();
}

?>
<script>
BX.CDialog.prototype.btnWdFolderSave = BX.CDialog.btnWdFolderSave = {
	title: BX.message('JS_CORE_WINDOW_SAVE'),
	id: 'savebtn',
	name: 'savebtn',
	action: function () {
        var tthis = BX.WindowManager.Get();
        this.disableUntilError();
        var url = tthis.PARAMS.content_url;
        BX.showWait();
        BX.ajax.post(url, tthis.GetParameters(), BX.delegate(function(result) {
			if (result.length > 0)
			{
				try
				{
					eval('arResult = ' + result);
				}
				catch(e) 
				{
					arResult = [];
				}
				if ('result' in arResult)
				{
					if (arResult['result'] == 'error')
					{
						alert(arResult['errmsg']);
						BX.closeWait();
						tthis.closeWait();
						return;
					}
				}
				//alert(result);
                tthis.Close();
			}
            BX.closeWait();
            if (tthis.reloadTree)
            {
                tthis.reloadTree();
                tthis.Close();
            } 
        }, tthis));
    }
};
BX(function() { var WDNameInput = BX('WDCreateFolderName'); if (WDNameInput) WDNameInput.focus();});
</script>
<?
$popupWindow = new CJSPopup('', '');


$popupWindow->ShowTitlebar(str_replace("#NAME#", $arResult["SECTION"]["NAME"], 
	($arParams["ACTION"] == "EDIT" ? GetMessage("WD_EDIT_SECTION") : (
		$arParams["ACTION"] == "ADD" ? GetMessage("WD_ADD_SECTION") : GetMessage("WD_DROP_SECTION")))));
if (!empty($arResult["ERROR_MESSAGE"]))
{
    $popupWindow->ShowValidationError($arResult["ERROR_MESSAGE"]);
    die();
}

$popupWindow->StartContent();
?>
<?=bitrix_sessid_post()?>
<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=$arResult["SECTION"]["IBLOCK_SECTION_ID"]?>" />
<input type="hidden" name="edit_section" value="Y" />
<input type="hidden" name="use_light_view" value="Y" />
<input type="hidden" name="ACTION" value="<?=$arParams["ACTION"]?>" />
<input type="hidden" name="ACTIVE" value="Y" />
<?
    $callParam = (!isset($_REQUEST["AJAX_CALL"])) ? "popupWindow" : "AJAX_CALL";
?>
<input type="hidden" name="<?=$callParam?>" value="Y" />

<?

if ($arParams["ACTION"] == "DROP"):
?>
	<?=str_replace("#NAME#", $arResult["SECTION"]["NAME"], GetMessage("WD_DROP_CONFIRM"))?>
<?
else:
?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="30%">
			<span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:
		</td>
		<td width="70%">
			<input id="WDCreateFolderName"type="text" class="text" name="NAME" value="<?=$arResult["SECTION"]["NAME"]?>" style="width:90%;" />
		</td>
	</tr>
</table>
<script type="text/javascript">
    BX.WindowManager.Get().PARAMS.content_url = "<?=CUtil::JSEscape(POST_FORM_ACTION_URI);?>";
</script>
<?
endif;

$popupWindow->EndContent();
?>
<script type="text/javascript">
<?=$popupWindow->jsPopup?>.SetButtons([<?=$popupWindow->jsPopup?>.btnWdFolderSave, <?=$popupWindow->jsPopup?>.btnCancel]);
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
