<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call-background.bundle.css',
	'js' => 'dist/call-background.bundle.js',
	'rel' => [
		'main.core',
		'im.lib.uploader',
		'im.lib.utils',
		'ui.info-helper',
		'ui.notification',
		'ui.vue',
		'ui.progressbarjs.uploader',
		'im.const',
	],
	'skip_core' => false,
];