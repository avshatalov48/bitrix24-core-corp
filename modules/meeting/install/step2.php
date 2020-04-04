<?
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

if($ex = $APPLICATION->GetException()):
	echo CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => GetMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
else:
	echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
	if ($_REQUEST['install_public']):
?>
<p><a href="<?=htmlspecialcharsbx($GLOBALS['meeting_folder'])?>/"><?=GetMessage("MEETING_INSTALL_GO")?></a></p>
<?
	endif;
endif;
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
<form>