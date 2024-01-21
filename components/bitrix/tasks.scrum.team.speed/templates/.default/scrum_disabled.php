<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\RestrictionUrl;

Extension::load('ui.alerts');

$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
	'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_SCRUM_OFF_SLIDER_URL,
	'SOURCE' => 'scrum',
]);
?>