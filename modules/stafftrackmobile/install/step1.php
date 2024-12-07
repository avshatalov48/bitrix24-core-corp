<?php

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

/** @var CMain $APPLICATION */

if ($exception = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage(Loc::getMessage('STAFFTRACKMOBILE_MODULE_INSTALL_ERROR') . '<br>' . $exception->GetString());
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<input type="hidden" name="lang" value="<?= LANG ?>">
	<input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>">
</form>