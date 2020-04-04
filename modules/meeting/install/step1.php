<?
$io = CBXVirtualIo::GetInstance();
$bChecked = !$io->FileExists($io->RelativeToAbsolutePath('/services/meeting/index.php'));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1" method="post">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANG?>" />
	<input type="hidden" name="id" value="meeting" />
	<input type="hidden" name="install" value="Y" />
	<input type="hidden" name="step" value="2" />
	<p><input type="checkbox" name="install_public" id="install_public" value="Y"<?=$bChecked ?  ' checked="checked"' : ''?> /><label for="install_public"><?echo GetMessage("MEETING_INSTALL_PUBLIC")?></label></p>
	<input type="submit" name="inst" value="<?echo GetMessage("MOD_INSTALL")?>" />
</form>