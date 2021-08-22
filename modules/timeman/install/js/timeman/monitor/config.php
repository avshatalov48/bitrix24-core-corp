<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/monitor.bundle.css',
	'js' => 'dist/monitor.bundle.js',
	'rel' => [
		'ui.vue.vuex',
		'main.md5',
		'main.sha1',
		'ui.forms',
		'ui.layout-form',
		'ui.alerts',
		'ui.vuex',
		'ui.vue.components.hint',
		'ui.dialogs.messagebox',
		'ui.icons',
		'timeman.component.timeline',
		'timeman.const',
		'ui.vue.portal',
		'ui.vue',
		'ui.notification',
		'main.popup',
		'main.loader',
		'timeman.dateformatter',
		'timeman.timeformatter',
		'pull.client',
		'main.core',
	],
	'skip_core' => false,
];