<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

\CAdminMessage::ShowNote(Loc::getMessage("MOD_UNINST_OK"));

/** @global \CMain $APPLICATION */
?>
<br>
<form action="<?= $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?= LANG?>">
	<input type="submit" value="<?= Loc::getMessage("MOD_BACK")?>">
</form>