<?php
/** @var CMain $APPLICATION */

IncludeModuleLangFile(__FILE__);

$haveConnection = false;
$configParams = \Bitrix\Main\Config\Configuration::getValue('connections');
if (is_array($configParams))
{
	include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/biconnector/lib/db/mysqliconnection.php';
	foreach ($configParams as $connectionParams)
	{
		if (is_a($connectionParams['className'], '\Bitrix\BIConnector\DB\MysqliConnection', true))
		{
			$haveConnection = true;
		}
	}
}
?>
<p><?= GetMessage('BICONNECTOR_INSTALL')?></p>
<form action="<?= $APPLICATION->GetCurPage()?>" name="form1">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID?>">
	<input type="hidden" name="id" value="biconnector">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">
	<p>
		<input type="checkbox" name="install_public" value="Y" id="id_install_public" checked>
		<label for="id_install_public"><?= GetMessage('COPY_PUBLIC_FILES') ?></label>
	</p>
	<p>
		<input type="checkbox" name="public_rewrite" value="Y" id="id_public_rewrite" checked>
		<label for="id_public_rewrite"><?= GetMessage('INSTALL_PUBLIC_REW') ?></label>
	</p>
	<?php
	if (!$haveConnection)
	{
		echo BeginNote() . GetMessage('BICONNECTOR_CONNECTION_NOTE') . EndNote();
	}
	?>
	<input type="submit" name="inst" value="<?= GetMessage('MOD_INSTALL')?>">
</form>
