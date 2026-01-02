<?php

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();

if ($userId > 0 && Loader::includeModule('humanresources'))
{
	$settings = Container::getHcmLinkSalaryAndVacationService()->getSettingsForFrontendByUser($userId);
}
else
{
	$settings = [
		'show' => false,
	];
}

return [
	'css' => 'dist/salary-vacation-menu.bundle.css',
	'js' => 'dist/salary-vacation-menu.bundle.js',
	'rel' => [
		'main.core',
		'main.core.cache',
		'main.popup',
	],
	'skip_core' => false,
	'settings' => $settings,
];
