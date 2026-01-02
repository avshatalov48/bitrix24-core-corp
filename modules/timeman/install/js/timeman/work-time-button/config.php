<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (
	!CBXFeatures::IsFeatureEnabled('timeman')
	|| !\Bitrix\Main\Loader::includeModule('timeman')
	|| !CTimeMan::canUse()
)
{
	return [];
}

$startInfo = CTimeMan::getRuntimeInfo();

$startInfo['PLANNER'] = $startInfo['PLANNER']['DATA'];

$userReport = new CUserReportFull;

return [
	'css' => 'dist/work-time-button.bundle.css',
	'js' => 'dist/work-time-button.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.buttons',
		'timeman',
		'CJSTask',
		'planner',
		'tasks_planner_handler',
		'calendar_planner_handler',
		'ajax',
		'timer',
		'popup',
		'ui.fonts.opensans',
		'ui.layout-form',
		'ui.analytics',
	],
	'skip_core' => false,
	'settings' => [
		'workReport' => $userReport->getReportData(),
		'info' => $startInfo,
		'siteId' => SITE_ID,
	],
];
