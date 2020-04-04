<?
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

//we skip UF errors in another modules.
$needToSkip = false;
$ex = $APPLICATION->GetException();
if($ex instanceof CAdminException)
{
	foreach($ex->GetMessages() as $exMessage)
	{
		if(isset($exMessage['id']) && $exMessage['id'] == 'FIELD_NAME')
		{
			$needToSkip = true;
		}
	}
	unset($exMessage);

}
if(!$needToSkip && $ex)
{
	echo CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => GetMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
}
else
{
	echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
}
?>
<div style="font-size: 12px;"></div>
<br>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
</form>
