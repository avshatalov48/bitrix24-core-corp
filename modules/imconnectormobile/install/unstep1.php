<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

IncludeModuleLangFile(__FILE__);
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="hidden" name="id" value="imconnectormobile">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">

	<?php CAdminMessage::ShowMessage(\Bitrix\Main\Localization\Loc::getMessage('IMCONNECTORMOBILE_MODULE_UNINSTALL_WARNING', ['#BR#' => '<br />']))?>

	<input type="submit" name="inst" value="<?=\Bitrix\Main\Localization\Loc::getMessage("MOD_UNINST_DEL")?>">
</form>