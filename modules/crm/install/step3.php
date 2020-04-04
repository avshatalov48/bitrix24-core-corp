<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
if (!check_bitrix_sessid()) return;

if (!empty($GLOBALS['errors'])):
	$m = new CAdminMessage(array('MESSAGE' => $error, 'TYPE' => 'ERROR', 'HTML' => true));
	foreach($GLOBALS['errors'] as $error):
		$m->message['MESSAGE'] = $error;
		echo $m->Show();
	endforeach;
else:
	CAdminMessage::ShowNote(GetMessage('MOD_INST_OK'));
endif;

?>
<form action='<?=$APPLICATION->GetCurPage()?>'>
	<input type='hidden' name='lang' value='<?echo LANGUAGE_ID?>'>
	<input type='submit' name='' value='<?echo GetMessage('MOD_BACK')?>'>
<form>