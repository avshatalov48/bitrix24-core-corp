<?
if(!check_bitrix_sessid()) return;

if (isset($disk_installer_errors) && is_array($disk_installer_errors) && (count($disk_installer_errors) > 0))
{
	$errors = '';
	foreach($disk_installer_errors as $e)
	{
		$errors .= htmlspecialcharsbx($e) . '<br>';
	}
	CAdminMessage::ShowMessage(Array(
		'TYPE' => 'ERROR',
		'MESSAGE' => GetMessage('MOD_UNINST_ERR'),
		'DETAILS' => $errors,
		'HTML' => true
	));
}
if($ex = $APPLICATION->GetException())
	CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => GetMessage("MOD_UNINST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
else
	CAdminMessage::ShowNote(GetMessage("MOD_UNINST_OK"));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
<form>
