<?php

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */

if (!check_bitrix_sessid())
{
	return;
}

\CAdminMessage::showNote(Loc::getMessage('MOD_UNINST_OK'));
?>

<br>
<form action="<?php echo $APPLICATION->getCurPage()?>">
	<input type="hidden" name="lang" value="<?php echo LANG?>">
	<input type="submit" name="" value="<?php echo Loc::getMessage('MOD_BACK')?>">
</form>
