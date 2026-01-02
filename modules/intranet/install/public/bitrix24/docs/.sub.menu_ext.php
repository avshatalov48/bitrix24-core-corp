<?php

use Bitrix\Main\IO\File;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$docsMenu = $_SERVER['DOCUMENT_ROOT'] . SITE_DIR . 'docs/.left.menu.php';
if (File::isFileExists($docsMenu))
{
	include($docsMenu);
}

$docsMenuExt = $_SERVER['DOCUMENT_ROOT'] . SITE_DIR . 'docs/.left.menu_ext.php';
if (File::isFileExists($docsMenuExt))
{
	if (!defined('SUB_MENU_EXT_CONTEXT'))
	{
		define('SUB_MENU_EXT_CONTEXT', true);
	}

	include($docsMenuExt);
}
