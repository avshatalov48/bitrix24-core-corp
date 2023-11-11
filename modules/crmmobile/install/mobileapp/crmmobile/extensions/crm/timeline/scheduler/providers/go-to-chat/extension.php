<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Context;

return [
	'region' => (Application::getInstance()->getLicense()->getRegion() ?? Context::getCurrent()->getLanguage()),
];
