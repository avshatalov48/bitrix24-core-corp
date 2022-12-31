<?php

global $APPLICATION;

if (!check_bitrix_sessid())
{
	return;
}

if ($exception = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage(GetMessage('CRMMOBILE_MODULE_INSTALL_ERROR') . '<br>' . $exception->GetString());
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<input type="hidden" name="lang" value="<?= LANG ?>">
	<input type="submit" name="" value="<?= GetMessage('MOD_BACK') ?>">
</form>