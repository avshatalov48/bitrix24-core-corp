<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

/**
 * @var $APPLICATION CMain
 */

if (!check_bitrix_sessid())
{
	return;
}

$ex = $APPLICATION->GetException();
if ($ex)
{
	echo CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => GetMessage('MOD_UNINST_ERR'),
		'DETAILS' => $ex->GetString(),
		'HTML' => true,
	]);
}
else
{
	echo CAdminMessage::ShowNote(GetMessage('MOD_UNINST_OK'));
}
?>
<form action="<?php echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?php echo LANGUAGE_ID?>">
	<input type="submit" name="" value="<?php echo GetMessage('MOD_BACK')?>">
	<form>
