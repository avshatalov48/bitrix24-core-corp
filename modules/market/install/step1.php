<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

/**
 * @var $APPLICATION CMain
 */

if ($ex = $APPLICATION->GetException()):
	echo CAdminMessage::ShowMessage(Array(
		"TYPE" => "ERROR",
		"MESSAGE" => GetMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
else:
	?>
	<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1" method="post">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="lang" value="<?=LANG?>" />
		<input type="hidden" name="id" value="market" />
		<input type="hidden" name="install" value="Y" />
		<input type="hidden" name="step" value="2" />
		<input type="submit" name="inst" value="<?echo GetMessage("MOD_INSTALL")?>" />
	</form>
<?php
endif;
