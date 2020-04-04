<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

IncludeModuleLangFile(__FILE__);

if (isset($webdav_installer_errors) && is_array($webdav_installer_errors) && (count($webdav_installer_errors) > 0))
{
	$errors = '';
	foreach($webdav_installer_errors as $e)
	{
		$errors .= htmlspecialcharsbx($e) . '<br>';
	}
	echo CAdminMessage::ShowMessage(Array(
		'TYPE' => 'ERROR',
		'MESSAGE' => GetMessage('MOD_UNINST_ERR'),
		'DETAILS' => $errors,
		'HTML' => true
	));
	?>
	<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
	</form>
	<?
}
?>