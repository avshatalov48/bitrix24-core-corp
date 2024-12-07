<?
if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);

if($ex = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage(Array(
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
	if (defined('BOT_CLIENT_URL'))
		$publicUrl = BOT_CLIENT_URL;
	else
		$publicUrl = (CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
	?>
	<div class="adm-info-message-wrap">
		<div class="adm-info-message">
			<div><?=GetMessage("TRANSFORMER_PUBLIC_PATH_DESC")?></div>
			<div><?=GetMessage("TRANSFORMER_PUBLIC_PATH_DESC_2", Array('#LINK_START#' => '', '#LINK_END#' => ''))?></div>
		</div>
	</div>
	<br>
	<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1" style="display: inline-block;">
		<table cellpadding="3" cellspacing="0" border="0" width="0%" class="adm-workarea">
			<tr>
				<td><?=GetMessage("TRANSFORMER_PUBLIC_PATH")?></td>
				<td><input type="text" name="PUBLIC_URL" value="<?=$publicUrl?>" size="40"></td>
			</tr>
		</table>
		<br><br>

		<?=bitrix_sessid_post()?>
		<input type="hidden" name="lang" value="<?echo LANG?>">
		<input type="hidden" name="id" value="transformer">
		<input type="hidden" name="install" value="Y">
		<input type="hidden" name="step" value="2">
		<input type="submit" name="inst" value="<?= GetMessage("MOD_INSTALL")?>">
	</form>
	<?
}
?>