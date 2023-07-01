<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;

return [
	'region' => (Application::getInstance()->getLicense()->getRegion()),
];
