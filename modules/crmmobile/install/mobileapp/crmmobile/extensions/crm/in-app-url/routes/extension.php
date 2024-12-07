<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'isUniversalActivityScenarioEnabled' => \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled(),
];