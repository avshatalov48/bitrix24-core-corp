<?php

define('NO_KEEP_STATISTIC', 'Y');
define('STOP_STATISTICS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (\Bitrix\Main\Loader::includeModule('voximplant'))
{
	include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/voximplant/controller_hit.php');
}
