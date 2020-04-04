<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (isset($ml_installer_errors) && is_array($ml_installer_errors) && (count($ml_installer_errors) > 0))
{
	$errors = "";
	foreach ($ml_installer_errors as $e)
		$errors .= htmlspecialcharsbx($e)."<br>";
	echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("MOD_UNINST_ERR"), "DETAILS"=>$errors, "HTML"=>true));
}
else
{
	echo CAdminMessage::ShowNote(GetMessage("MOD_UNINST_OK"));
}
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
</form>
