<?php

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

global $errors, $APPLICATION;

if(empty($errors))
{
	CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK'));
}
else
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
		'DETAILS' => implode('<br>', is_array($errors) ? $errors : []),
		'HTML' => true,
	]);
}
if ($ex = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
		'HTML' => true,
		'DETAILS' => $ex->GetString()
	]);
}
?>
<form action='<?= $APPLICATION->GetCurPage() ?>'>
	<input type='hidden' name='lang' value='<?= LANG ?>'>
	<input type='submit' name='' value='<?= Loc::getMessage('MOD_BACK') ?>'>
<form>
