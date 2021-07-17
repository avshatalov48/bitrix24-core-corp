<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!WIZARD_IS_INSTALLED)
{
	if (IsModuleInstalled("bitrix24"))
	{
		$file = fopen(WIZARD_SITE_ROOT_PATH."/bitrix/php_interface/dbconn.php", "ab");
		fwrite($file, file_get_contents(WIZARD_ABSOLUTE_PATH."/site/services/files/bitrix/dbconn.php"));
		fclose($file);

		CopyDirFiles(
			$_SERVER['DOCUMENT_ROOT']."/bitrix/modules/bitrix24/public/",
			WIZARD_SITE_PATH,
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = false
		);
	}

	CopyDirFiles(
		$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/install/public/pub/',
		WIZARD_SITE_PATH."/pub/",
		$rewrite = true,
		$recursive = true,
		$delete_after_copy = false
	);

	CopyDirFiles(
		$_SERVER['DOCUMENT_ROOT']."/bitrix/modules/intranet/install/public/bitrix24/",
		WIZARD_SITE_PATH,
		$rewrite = true,
		$recursive = true,
		$delete_after_copy = false
	);

	if (!IsModuleInstalled("bitrix24"))
	{
		CopyDirFiles(
			WIZARD_ABSOLUTE_PATH . "/site/services/files/bitrix/init.php",
			WIZARD_SITE_ROOT_PATH . "/bitrix/php_interface/init.php",
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = false
		);
	}
}