<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;

if (!check_bitrix_sessid())
{
	return;
}

if ($exception = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => \Bitrix\Main\Localization\Loc::getMessage('MOD_INST_ERR'),
		'DETAILS' => $exception->GetString(),
		'HTML' => true,
	]);
}
else
{
	CAdminMessage::ShowNote(\Bitrix\Main\Localization\Loc::getMessage('MOD_INST_OK'));
}
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=\Bitrix\Main\Localization\Loc::getMessage('MOD_BACK')?>">
<form>
