<?php
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'isMobileMailScenarioEnabled' => Loader::includeModule('crm') && \Bitrix\Crm\Settings\Crm::isMobileMailScenarioEnabled(),
];
