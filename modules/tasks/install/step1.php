<?if(!check_bitrix_sessid()) return;?>
<?
global $errors;

if(!is_array($errors) && strlen($errors)<=0 || is_array($errors) && count($errors) <= 0)
{
	echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
}
else
{
	$alErrors = implode('<br>', $errors);
	echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("MOD_INST_ERR"), "DETAILS"=>$alErrors, "HTML"=>true));
}
if ($ex = $APPLICATION->GetException())
{
	echo CAdminMessage::ShowMessage(Array("TYPE" => "ERROR", "MESSAGE" => GetMessage("MOD_INST_ERR"), "HTML" => true, "DETAILS" => $ex->GetString()));
}
?>
<form action='<?=$APPLICATION->GetCurPage()?>'>
	<input type='hidden' name='lang' value='<?echo LANG?>'>
	<input type='submit' name='' value='<?echo GetMessage('MOD_BACK')?>'>
<form>