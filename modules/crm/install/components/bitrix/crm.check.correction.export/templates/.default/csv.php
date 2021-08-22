<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$isStExport = (isset($arResult['STEXPORT_MODE']) && $arResult['STEXPORT_MODE'] === 'Y');
$isFirstPage = $arResult['IS_FIRST_PAGE'] === 'Y';
$isLastPage = $arResult['IS_LAST_PAGE'] === 'Y';

if ($isFirstPage)
{
	foreach ($arResult['HEADERS'] as $key => $header)
	{
		echo '"' . $header['content'] . '";';
		if ($key === 'SUM')
		{
			echo '"' . Loc::getMessage('CRM_COLUMN_CURRENCY') . '";';
		}
	}

	echo "\n";
}

foreach ($arResult['ENTRIES'] as $entry)
{
	foreach ($arResult['HEADERS'] as $fieldName => $header)
	{
		echo ($entry[$fieldName] !== '') ? '"'.str_replace('"', '""', $entry[$fieldName]).'";' : ';';
		if ($fieldName === 'SUM')
		{
			echo ($entry['CURRENCY'] !== '') ? '"'.str_replace('"', '""', $entry['CURRENCY']).'";' : ';';
		}
	}

	echo "\n";
}