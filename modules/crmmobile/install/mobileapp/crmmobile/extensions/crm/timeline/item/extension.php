<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;

return [
	'isBizprocActivityAvailable' => (bool)Option::get('crmmobile', 'bizproc_activity_available', false),
];
