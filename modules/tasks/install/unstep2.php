<?php

use Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid())
{
	return;
}

global $APPLICATION;
if ($ex = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_UNINST_ERR'),
		'DETAILS' => $ex->GetString(),
		'HTML' => true,
	]);
}

else
{
	CAdminMessage::ShowNote(Loc::getMessage('MOD_UNINST_OK'));
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<input type="hidden" name="lang" value="<?= LANG ?>">
	<input type="submit" name="" value="<?= Loc::getMessage('MOD_BACK') ?>">
<form>
