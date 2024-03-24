<?php

use Bitrix\Main\Loader;
use Bitrix\Voximplant\Security\Helper;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'taskNewChecklistActive' => Bitrix\Main\Config\Option::get('tasksmobile', 'taskNewChecklistActive', 'N') === 'Y',
];
