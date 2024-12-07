<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="hidden" name="id" value="stafftrackmobile">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">

	<?php CAdminMessage::ShowMessage(GetMessage('STAFFTRACKMOBILE_MODULE_UNINSTALL_WARNING', ['#BR#' => '<br />']))?>

	<input type="submit" name="inst" value="<?= Loc::getMessage("MOD_UNINST_DEL")?>">
</form>