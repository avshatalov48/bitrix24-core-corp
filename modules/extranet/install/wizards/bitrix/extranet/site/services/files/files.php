<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CopyDirFiles(
	WIZARD_ABSOLUTE_PATH . "/site/public/",
	WIZARD_SITE_PATH,
	$rewrite = (WIZARD_B24_TO_CP) ? true : false, 
	$recursive = true,
	$delete_after_copy = false,
	$exclude = "bitrix"
);

CopyDirFiles(
	$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/rest/install/public/marketplace/',
	WIZARD_SITE_PATH."/marketplace/",
	$rewrite = (WIZARD_B24_TO_CP) ? true : false,
	$recursive = true
);

switch (LANGUAGE_ID)
{
	case 'en':
		$dateTimeFormat = 'F j, Y h:i a';
		break;
	case 'de':
		$dateTimeFormat = 'j. F Y H:i:s';
		break;
	default:
		$dateTimeFormat = 'd.m.Y H:i:s';
}

switch (LANGUAGE_ID)
{
	case 'ru':
		$aboutLifeRss = 'https://www.1c-bitrix.ru/about/life/news/rss/';
		break;
	case 'de':
		$aboutLifeRss = 'https://www.bitrix.de/company/news/rss/';
		break;
	default:
		$aboutLifeRss = 'https://www.bitrixsoft.com/company/news/rss/';
}

CWizardUtil::ReplaceMacrosRecursive(
	WIZARD_SITE_PATH,
	Array(
		"SITE_DIR" => WIZARD_SITE_DIR,
		"DATE_TIME_FORMAT" => $dateTimeFormat,
		"ABOUT_LIFE_RSS" => $aboutLifeRss,
	)
);

$APPLICATION->SetFileAccessPermission(
	WIZARD_SITE_DIR."confirm/", 
	array("2" => "R")
);
