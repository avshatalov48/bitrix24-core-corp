<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!check_bitrix_sessid())
{
	return;
}

CAdminMessage::ShowNote(\Bitrix\Main\Localization\Loc::getMessage('MOD_UNINST_OK'));
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=\Bitrix\Main\Localization\Loc::getMessage('MOD_BACK')?>">
<form>
