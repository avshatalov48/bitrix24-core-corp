<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
if ($_REQUEST["AJAX_CALL"] == "Y")
{
	$GLOBALS['APPLICATION']->RestartBuffer();
}
?>
<div class="wd-window-edit">
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data" onsubmit="if(window.WDOnSubmitForm){WDOnSubmitForm(this); return false;}else{return true;}" class="wd-form">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="SECTION_ID" value="<?=$arParams["SECTION_ID"]?>" />
	<input type="hidden" name="IBLOCK_SECTION_ID" value="<?=$arResult["SECTION"]["IBLOCK_SECTION_ID"]?>" />
	<input type="hidden" name="edit_section" value="Y" />
	<input type="hidden" name="ACTION" value="<?=$arParams["ACTION"]?>" />
	<input type="hidden" name="ACTIVE" value="Y" />
	<input type="hidden" name="cancel" value="" />
<table cellpadding="0" cellspacing="0" border="0" class="wd-window-edit">
	<thead>
		<tr>
			<td>
			<?=str_replace("#NAME#", $arResult["SECTION"]["NAME"], 
				($arParams["ACTION"] == "EDIT" ? GetMessage("WD_EDIT_SECTION") : (
					$arParams["ACTION"] == "ADD" ? GetMessage("WD_ADD_SECTION") : GetMessage("WD_DROP_SECTION"))))?></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><div class="content">
<?
if (!empty($arResult["ERROR_MESSAGE"]))
{
	ShowError($arResult["ERROR_MESSAGE"]);
}

if ($arParams["ACTION"] == "DROP"):
?>
	<?=str_replace("#NAME#", $arResult["SECTION"]["NAME"], GetMessage("WD_DROP_CONFIRM"))?>
<?
else:
?>
	<div class="wd-filed-name name"><span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:</div>
	<div class="wd-filed name"><input type="text" class="text" name="NAME" value="<?=$arResult["SECTION"]["NAME"]?>" /></div>
<?
endif;
?>	
			</div></td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td>
<?
if ($arParams["ACTION"] == "DROP"):
?>
				<input type="submit" name="wd_drop" value="<?=GetMessage("WD_DROP")?>" />
				<input type="button" name="wd_cancel" value="<?=GetMessage("WD_CANCEL")?>" onclick="if(window.WDOnCancelForm!=null){WDOnCancelForm(this);}else{this.form['cancel'].value='Y'; this.form.submit();}" />
<?
else:
?>
				<input type="submit" name="wd_submit" value="<?=GetMessage("WD_SAVE");?>" />
				<input type="button" name="wd_cancel" value="<?=GetMessage("WD_CANCEL");?>" onclick="if(window.WDOnCancelForm!=null){WDOnCancelForm(this);}else{this.form['cancel'].value='Y'; this.form.submit();}" />
<?
endif;
?>
		</td></tr>
	</tfoot>
</table>
</form>
</div>
<?
if ($_REQUEST["AJAX_CALL"] == "Y")
{
	die();
}
?>
