<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponentTemplate $this
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 */
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

Header('Content-Type: text/csv');
Header('Content-Disposition: attachment;filename=crm_items.csv');
Header('Content-Type: application/octet-stream');
Header('Content-Transfer-Encoding: binary');

if ($arResult['FIRST_EXPORT_PAGE'])
{
	array_map(function($header) {
		echo '"', str_replace('"', '""', $header['name']),'";';
	}, $arResult['HEADERS']);
	echo "\n";
}

foreach ($arResult['ITEMS'] as $item)
{
	array_map(function($header) use ($item) {
		echo '"', str_replace('"', '""', htmlspecialcharsback($item[$header['id']])), '";';
	}, $arResult['HEADERS']);
	echo "\n";
}

