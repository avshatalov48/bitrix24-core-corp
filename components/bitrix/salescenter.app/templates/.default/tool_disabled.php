<?php

use Bitrix\Main\UI\Extension;
use Bitrix\Salescenter\Restriction\ToolAvailabilityManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

print ToolAvailabilityManager::getInstance()->getSalescenterStubContent();
