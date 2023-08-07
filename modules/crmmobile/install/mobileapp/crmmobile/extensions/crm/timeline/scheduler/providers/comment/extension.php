<?php

use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'isCommentAvailable' => (bool)Option::get('crmmobile', 'crm-23.900.0-available', false),
];
