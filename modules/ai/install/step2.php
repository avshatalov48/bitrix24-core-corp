<?php
use \Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

/** @var \CMain $APPLICATION */

if ($ex = $APPLICATION->getException())
{
	\CAdminMessage::showMessage([
		'TYPE'    => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
		'DETAILS' => $ex->getString(),
		'HTML'    => true,
	]);
}
else
{
	\CAdminMessage::showNote(Loc::getMessage('MOD_INST_OK'));
}
?>
<form action="<?= $APPLICATION->getCurPage()?>">
	<input type="hidden" name="lang" value="<?= LANG?>">
	<input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK')?>">
</form>
