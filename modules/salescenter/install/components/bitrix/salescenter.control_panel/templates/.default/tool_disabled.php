<?php

use Bitrix\Main\UI\Extension;
use Bitrix\Salescenter\Restriction\ToolAvailabilityManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

// the js breaks if we don't include it
Extension::load([
	'ui.tilegrid',
]);

print ToolAvailabilityManager::getInstance()->getSalescenterStubContent();
