<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'duplicateControlEnableFor' => Bitrix\Crm\Integrity\DuplicateControl::loadCurrentSettings(),
];