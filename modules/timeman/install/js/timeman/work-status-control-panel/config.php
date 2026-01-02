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
	'css' => 'dist/work-status-control-panel.bundle.css',
	'js' => 'dist/work-status-control-panel.bundle.js',
	'rel' => [
		'ui.vue3',
		'timeman',
		'CJSTask',
		'planner',
		'tasks_planner_handler',
		'calendar_planner_handler',
		'ajax',
		'timer',
		'popup',
		'main.core',
		'main.core.events',
		'ui.icon-set.api.vue',
		'ui.vue3.components.button',
	],
	'skip_core' => false,
	'settings' => [
		'workReport' => $userReport->getReportData(),
		'info' => $startInfo,
		'siteId' => SITE_ID,
	],
];
