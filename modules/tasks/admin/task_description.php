<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// map action tasks into their lang names

return [
	"TASKS_TASK_TEMPLATE_READ" => [
		"title" => Loc::getMessage("TASK_NAME_TASK_TEMPLATE_READ"),
		"description" => Loc::getMessage("TASK_NAME_TASK_TEMPLATE_READ"),
	],
	"TASKS_TASK_TEMPLATE_FULL" => [
		"title" => Loc::getMessage("TASK_NAME_TASK_TEMPLATE_FULL"),
		"description" => Loc::getMessage("TASK_NAME_TASK_TEMPLATE_FULL"),
	],
];