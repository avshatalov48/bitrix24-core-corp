<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\DI\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Loader::includeModule('tasks'))
{
	return [];
}

$configService = Container::getInstance()->getConfigService();
$userId = (int)CurrentUser::get()->getId();

return [
	'js' => 'dist/core.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3.vuex',
		'tasks.v2.model.check-list',
		'tasks.v2.model.crm-items',
		'tasks.v2.model.epics',
		'tasks.v2.model.flows',
		'tasks.v2.model.groups',
		'tasks.v2.model.interface',
		'tasks.v2.model.stages',
		'tasks.v2.model.tasks',
		'tasks.v2.model.users',
		'tasks.v2.provider.pull.pull-manager',
	],
	'skip_core' => false,
	'settings' => $configService->getCoreSettings($userId),
];
