<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arResult = [];

$arResult['showCalltrackerSettings'] = \Bitrix\Mobile\Integration\Crm\CallTracker::isAvailable();

return $arResult;