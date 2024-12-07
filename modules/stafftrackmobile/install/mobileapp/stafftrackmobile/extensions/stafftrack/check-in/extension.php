<?php

use Bitrix\Main\Loader;
use Bitrix\StaffTrack\Dictionary\CancelReason;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$cancelReasonList = [];
if (Loader::includeModule('stafftrack'))
{
	$cancelReasonList = CancelReason::getList();
}

return [
	'cancelReasonList' => $cancelReasonList,
];
