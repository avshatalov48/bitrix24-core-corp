<?php

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */

if (!check_bitrix_sessid())
{
	return;
}

if ($ex = $APPLICATION->getException())
{
	\CAdminMessage::showMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
		'DETAILS' => $ex->getString(),
		'HTML' => true
	]);
}
else
{
	\CAdminMessage::showNote(Loc::getMessage('MOD_INST_OK'));
}
?>

<form action="<?php echo $APPLICATION->getCurPage()?>">
	<input type="hidden" name="lang" value="<?php echo LANG?>">
	<input type="submit" name="" value="<?php echo Loc::getMessage('MOD_BACK')?>">
</form>
