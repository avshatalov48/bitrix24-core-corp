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
		'ui.notification',
		'ui.forms',
		'ui.layout-form',
		'ui.alerts',
		'ui.vuex',
		'ui.vue.components.hint',
		'ui.vue.portal',
		'main.popup',
		'ui.dialogs.messagebox',
		'ui.icons',
		'ui.vue',
		'timeman.component.timeline',
		'timeman.const',
		'main.loader',
		'timeman.dateformatter',
		'timeman.timeformatter',
		'pull.client',
		'main.core',
	],
	'skip_core' => false,
];