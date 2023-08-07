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
		'timeman.const',
		'ui.notification',
		'timeman.monitor-report',
		'timeman.dateformatter',
		'timeman.timeformatter',
		'pull.client',
		'main.core',
		'im.v2.lib.desktop-api',
	],
	'skip_core' => false,
];