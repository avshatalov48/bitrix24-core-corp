<?php

use Bitrix\Main\Loader;
use Bitrix\StaffTrack\Dictionary\Location;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$locationList = [];
if (Loader::includeModule('stafftrack'))
{
	$locationList = Location::getList();
}

return [
	'locationList' => $locationList,
];
