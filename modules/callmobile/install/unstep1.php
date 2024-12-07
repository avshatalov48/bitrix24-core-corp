<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @global \CMain $APPLICATION */
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="hidden" name="id" value="callmobile">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">

	<?php \CAdminMessage::ShowMessage(Loc::getMessage('CALLMOBILE_MODULE_UNINSTALL_WARNING', ['#BR#' => '<br />']))?>

	<input type="submit" name="inst" value="<?= Loc::getMessage("MOD_UNINST_DEL")?>">
</form>