<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/monitor-report.bundle.css',
	'js' => 'dist/monitor-report.bundle.js',
	'rel' => [
		'ui.forms',
		'ui.layout-form',
		'ui.vuex',
		'ui.vue.components.hint',
		'ui.dialogs.messagebox',
		'ui.icons',
		'ui.fonts.opensans',
		'timeman.component.timeline',
		'timeman.timeformatter',
		'timeman.monitor',
		'ui.vue.portal',
		'ui.notification',
		'main.core',
		'ui.vue',
		'timeman.const',
		'timeman.dateformatter',
		'main.popup',
		'main.loader',
		'ui.pinner',
		'ui.alerts',
		'ui.design-tokens',
	],
	'skip_core' => false,
];