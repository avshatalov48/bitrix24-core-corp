<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Site\Sections\TimemanSection;
use Bitrix\Main\Loader;

if (!Loader::includeModule('intranet'))
{
	return;
}

$aMenuLinks = [];
foreach (TimemanSection::getItems() as $item)
{
	if ($item['available'])
	{
		$aMenuLinks[] = [
			$item['title'] ?? '',
			$item['url'] ?? '',
			$item['extraUrls'] ?? [],
			$item['menuData'] ?? [],
			'',
		];
	}
}