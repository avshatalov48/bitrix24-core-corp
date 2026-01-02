<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'./dist/registry.bundle.js',
	],
	'rel' => [
		'im.v2.lib.feature',
		'im.public',
		'im.v2.provider.service',
		'imopenlines.v2.lib.openlines',
		'im.v2.lib.role-manager',
		'im.v2.lib.uuid',
		'im.v2.lib.layout',
		'im.v2.lib.copilot',
		'ui.vue3.vuex',
		'ui.notification',
		'im.v2.lib.user',
		'rest.client',
		'im.v2.lib.notifier',
		'im.v2.lib.utils',
		'main.core',
		'ui.uploader.core',
		'im.v2.lib.logger',
		'main.core.events',
		'im.v2.lib.analytics',
		'im.v2.application.core',
		'im.v2.const',
		'im.v2.lib.rest',
	],
	'skip_core' => false,
];
