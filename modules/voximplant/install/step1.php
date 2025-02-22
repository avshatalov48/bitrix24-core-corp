<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!check_bitrix_sessid())
{
	return;
}

use Bitrix\Main\Localization\Loc;


/**
 * @global CMain $APPLICATION
 */

$ex = $APPLICATION->GetException();
if ($ex)
{
	\CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => Loc::getMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=Loc::getMessage("MOD_BACK")?>">
</form>

<?php
}
else
{
	if (defined('VOXIMPLANT_CLIENT_URL'))
	{
		$publicUrl = VOXIMPLANT_CLIENT_URL;
	}
	else
	{
		$publicUrl = (CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
	}

?>
	<div class="adm-info-message-wrap">
		<div class="adm-info-message">
			<div><?=Loc::getMessage("VI_PUBLIC_PATH_DESC")?></div>
			<div><?=Loc::getMessage("VI_PUBLIC_PATH_DESC_2", Array('#LINK_START#' => '<a href="'.(in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=48&LESSON_ID=4869': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=6704').'" target="_blank">', '#LINK_END#' => '</a>'))?></div>
		</div>
	</div>
	<br>
	<form action="<?=$APPLICATION->GetCurPage()?>" name="form1" style="display: inline-block;">
		<table cellpadding="3" cellspacing="0" border="0" width="0%" class="adm-workarea">
		<tr>
			<td><?=Loc::getMessage("VI_PUBLIC_PATH")?></td>
			<td><input type="text" name="PUBLIC_URL" value="<?=$publicUrl?>" size="40"></td>
		</tr>
		</table>
		<br><br>

		<?=bitrix_sessid_post()?>
		<input type="hidden" name="lang" value="<?=LANG?>">
		<input type="hidden" name="id" value="voximplant">
		<input type="hidden" name="install" value="Y">
		<input type="hidden" name="step" value="2">
		<input type="submit" name="inst" value="<?=Loc::getMessage("MOD_INSTALL")?>">
	</form>
<?php
}
