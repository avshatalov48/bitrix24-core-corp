<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS['APPLICATION']->RestartBuffer();
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/template.php")));
__IncludeLang($file);

$popupWindow = new CJSPopup('', '');

//HTML output
$sTitle = GetMessage("WD_EDIT_SECTION"); 
$sDescription = GetMessage("WD_EDIT_SECTION_DESCRIPTION"); 
$sTheme = "bx-property-folder"; 

if ($arParams["ACTION"] == "ADD")
{
	$sTitle = GetMessage("WD_ADD_SECTION"); 
	$sDescription = GetMessage("WD_ADD_SECTION_DESCRIPTION"); 
	$sTheme = "bx-create-new-folder"; 
}
elseif ($arParams["ACTION"] == "DROP")
{
	$sTitle = GetMessage("WD_DROP_SECTION"); 
	$sDescription = GetMessage("WD_DROP_SECTION_DESCRIPTION"); 
	$sTheme = "bx-delete-page"; 
}

if (!empty($arResult["ERROR_MESSAGE"]))
{
	$popupWindow->ShowValidationError($arResult["ERROR_MESSAGE"]);
	die();
}

$popupWindow->ShowTitlebar($sTitle);
$popupWindow->StartDescription();
?><p><?=str_replace("#PATH#", "/".implode("/", $arResult["NAV_CHAIN"]), $sDescription)?></p><?
$popupWindow->EndDescription();


$popupWindow->StartContent();
?>
	<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
	<input type="hidden" name="edit_section" value="Y" />
	<input type="hidden" name="popupWindow" value="Y" />
	<input type="hidden" name="ACTION" value="<?=$arParams["ACTION"]?>" />
<?if (!empty($_REQUEST["back_url"])): ?>
	<input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($_REQUEST["back_url"])?>" />
<?endif;

if ($arParams["ACTION"] == "DROP"):
?>
	<?=str_replace("#NAME#", $arResult["SECTION"]["NAME"], GetMessage("WD_DROP_CONFIRM"))?>
<?
else:
?>
	<table cellpadding="0" cellspacing="0" border="0" class="edit-table" id="edit2_edit_table" width="100%">
<?

if ($arParams["ACTION"] == "EDIT"):
?>
		<tr>
			<td width="40%" align="right"><?=GetMessage("WD_PARENT_SECTION")?>:</td>
			<td width="60%">
				<input type="hidden" name="IBLOCK_SECTION_ID" readonly="readonly" value="<?=intval($_REQUEST["IBLOCK_SECTION_ID"])?>" />
				<input type="button" name="" value="..." /></td>
		</tr>
<?
else:
?>
	<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=$arResult["SECTION"]["IBLOCK_SECTION_ID"]?>" />
<?
endif;
?>
		<tr>
			<td width="40%" align="right"><span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:</td>
			<td width="60%"><input type="text" name="NAME" value="<?=$arResult["SECTION"]["NAME"]?>" style="width:90%;" /></td>
		</tr>
	</table>
<?
endif;

?>
<script type="text/javascript">
	BX.WindowManager.Get().PARAMS.content_url = "<?=CUtil::JSEscape(POST_FORM_ACTION_URI);?>";
</script>
<?
$popupWindow->EndContent();
$popupWindow->ShowStandardButtons();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
