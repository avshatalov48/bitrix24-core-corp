<?php
use \Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

/** @var \CMain $APPLICATION */

\CAdminMessage::showNote(Loc::getMessage('MOD_UNINST_OK'));
?>

<br>
<form action="<?= $APPLICATION->getCurPage()?>">
	<input type="hidden" name="lang" value="<?= LANG?>">
	<input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK')?>">
</form>
