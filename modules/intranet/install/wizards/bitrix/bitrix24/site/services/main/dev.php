<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Bitrix\Main\Loader::includeModule("bitrix24"))
{
	return;
}

// Dev Environment
$devUpdater = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/bitrix24/dev/environment.php";
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/.dev") && file_exists($devUpdater))
{
	include($devUpdater);
}
