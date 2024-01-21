<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

if (Loader::includeModule('intranet'))
{
	$aMenuLinks = \Bitrix\Intranet\Site\Sections\AutomationSection::getBizProcSubMenu();
}
