<?php
use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */

if ($exception = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage(GetMessage('SIGN_MODULE_UNINSTALL_ERROR') . '<br>' . $exception->GetString());
	?>
	<form action="<?= $APPLICATION->GetCurPage() ?>">
		<p>
			<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
			<input type="submit" name="" value="<?= GetMessage('MOD_BACK') ?>">
		</p>
	</form>
	<?php
}
else
{
?>
	<form action="<?php echo $APPLICATION->getCurPage()?>">
		<?php echo bitrix_sessid_post()?>
		<input type="hidden" name="lang" value="<?php echo LANG?>">
		<input type="hidden" name="id" value="sign">
		<input type="hidden" name="uninstall" value="Y">
		<input type="hidden" name="step" value="2">
		<input type="hidden" name="savedata" value="N">
		<p><?php echo Loc::getMessage('MOD_UNINST_SAVE')?></p>
		<p>
			<input type="checkbox" name="savedata" id="savedata" value="Y" checked>
			<label for="savedata"><?php echo Loc::getMessage('MOD_UNINST_SAVE_TABLES')?></label>
		</p>
		<input type="submit" name="inst" value="<?php echo Loc::getMessage('MOD_UNINST_DEL')?>">
	</form>
	<?php
}