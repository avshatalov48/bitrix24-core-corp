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
	'js' => 'dist/analytics.bundle.js',
	'rel' => [
		'main.core',
		'ui.analytics',
		'ui.uploader.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
	'settings' => $configService->getAnalyticsSettings($userId),
];
