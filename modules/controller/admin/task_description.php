<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return [
	'CONTROLLER_DENY' => [
		'title' => Loc::getMessage('TASK_NAME_CONTROLLER_DENY'),
		'description' => Loc::getMessage('TASK_DESC_CONTROLLER_DENY'),
	],
	'CONTROLLER_AUTH' => [
		'title' => Loc::getMessage('TASK_NAME_CONTROLLER_AUTH'),
		'description' => Loc::getMessage('TASK_DESC_CONTROLLER_AUTH'),
	],
	'CONTROLLER_READ' => [
		'title' => Loc::getMessage('TASK_NAME_CONTROLLER_READ'),
		'description' => Loc::getMessage('TASK_DESC_CONTROLLER_READ'),
	],
	'CONTROLLER_ADD' => [
		'title' => Loc::getMessage('TASK_NAME_CONTROLLER_ADD'),
		'description' => Loc::getMessage('TASK_DESC_CONTROLLER_ADD'),
	],
	'CONTROLLER_SITE' => [
		'title' => Loc::getMessage('TASK_NAME_CONTROLLER_SITE'),
		'description' => Loc::getMessage('TASK_DESC_CONTROLLER_SITE'),
	],
	'CONTROLLER_FULL' => [
		'title' => Loc::getMessage('TASK_NAME_CONTROLLER_FULL'),
		'description' => Loc::getMessage('TASK_DESC_CONTROLLER_FULL'),
	],
	'CONTROLLER' => [
		'title' => Loc::getMessage('TASK_BINDING_CONTROLLER'),
	],
];
