<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$path = $_SERVER['DOCUMENT_ROOT'] .
	'/bitrix/components/bitrix/timeman.worktime.grid/templates/.default/result_modifier.php'
;

if (file_exists($path))
{
	require $path;
}

if (!is_array($arResult['ROWS']))
{
	$arResult['ROWS'] = [];
}

foreach ($arResult['ROWS'] as &$row)
{
	foreach ($row['columns'] as &$value)
	{
		$value = strip_tags($value);
	}
}