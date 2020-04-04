<?
IncludeModuleLangFile(__FILE__);
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="hidden" name="id" value="imbot">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<div class="adm-info-message-wrap">
		<div class="adm-info-message">
			<div><?echo GetMessage("IMBOT_UNINSTALL_TITLE")?></div>
		</div>
	</div>
	<input type="submit" name="inst" value="<?echo GetMessage("MOD_UNINST_DEL")?>">
</form>