<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!file_exists(WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/"))
{
	switch (LANGUAGE_ID)
	{
		case 'ua':
			$publicLang = 'ru';
			break;
		default:
			$publicLang = 'en';
	}
}
else
{
	$publicLang = LANGUAGE_ID;
}

CopyDirFiles(
	WIZARD_ABSOLUTE_PATH."/site/public/".$publicLang."/",
	WIZARD_SITE_PATH,
	$rewrite = (WIZARD_B24_TO_CP) ? true : false, 
	$recursive = true,
	$delete_after_copy = false,
	$exclude = "bitrix"
);

CWizardUtil::ReplaceMacrosRecursive(WIZARD_SITE_PATH, Array("SITE_DIR" => WIZARD_SITE_DIR));

$APPLICATION->SetFileAccessPermission(
	WIZARD_SITE_DIR."confirm/", 
	array("2" => "R")
);
?>