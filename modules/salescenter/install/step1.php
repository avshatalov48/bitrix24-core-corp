<?
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

if($ex = $APPLICATION->GetException())
{
	echo CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => GetMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
	?>
	<form action="<?echo $APPLICATION->GetCurPage()?>">
		<input type="hidden" name="lang" value="<?echo LANG?>">
		<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
	</form>
	<?
}
else
{
	?>
	<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1" style="display: inline-block;">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="lang" value="<?echo LANG?>">
		<input type="hidden" name="id" value="salescenter">
		<input type="hidden" name="install" value="Y">
		<input type="hidden" name="step" value="2">
		<input type="submit" name="inst" value="<?= GetMessage("MOD_INSTALL")?>">
	</form>
	<?
}
?>