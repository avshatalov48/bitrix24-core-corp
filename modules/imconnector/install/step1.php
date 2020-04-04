<?
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

if (!\check_bitrix_sessid())
	return;

if($ex = $APPLICATION->GetException())
{
	echo CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => Loc::getMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo Loc::getMessage("MOD_BACK")?>">
</form>
<?
}
else
{
if (defined('CONNECTOR_CLIENT_URL'))
{
	$publicUrl = CONNECTOR_CLIENT_URL;
}
else
{
	$server = Context::getCurrent()->getServer();
	$request = Context::getCurrent()->getRequest();

	$publicUrl = ($request->isHttps() ? 'https://' :  'http://')  . $server->getServerName() .
		(($server->getServerPort() == 80 || ($server->get('HTTPS') && $server->getServerPort() == 443) ) ?  "" : ":" . $server->getServerPort());
}
?>
<div class="adm-info-message-wrap">
	<div class="adm-info-message">
		<div><?=Loc::getMessage("IMCONNECTOR_PUBLIC_PATH_DESC")?></div>
		<div><?=Loc::getMessage("IMCONNECTOR_PUBLIC_PATH_DESC_2", Array('#LINK_START#' => '', '#LINK_END#' => ''))?></div>
	</div>
</div>
<br>
<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1" style="display: inline-block;">
	<table cellpadding="3" cellspacing="0" border="0" width="0%" class="adm-workarea">
	<tr>
		<td><?=Loc::getMessage("IMCONNECTOR_PUBLIC_PATH")?></td>
		<td><input type="text" name="public_url" value="<?=$publicUrl?>" size="40"></td>
	</tr>
	</table>
	<br><br>

	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
	<input type="hidden" name="id" value="imconnector">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">
	<input type="submit" name="inst" value="<?= Loc::getMessage("MOD_INSTALL")?>">
</form>
<?
}
?>