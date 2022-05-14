<?php

use Bitrix\Main\IO\File;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$companyMenu = $_SERVER['DOCUMENT_ROOT'] . '/company/.left.menu_ext.php';
if (File::isFileExists($companyMenu))
{
	include($companyMenu);
}
