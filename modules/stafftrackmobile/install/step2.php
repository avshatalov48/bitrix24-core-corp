<?php
use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */


if (!check_bitrix_sessid())
{
	return;
}

if ($exception = $APPLICATION->GetException())
{
	\CAdminMessage::showMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
		'DETAILS' => $exception->getString(),
		'HTML' => true
	]);
}
else
{
	CAdminMessage::ShowNote(GetMessage('MOD_INST_OK'));
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<input type="hidden" name="lang" value="<?= LANG ?>">
	<input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>">
</form>