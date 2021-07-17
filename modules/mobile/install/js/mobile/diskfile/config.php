<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => './dist/diskfile.bundle.js',
//	'css' => '/bitrix/js/mobile/livefeed/mobile.diskfile.css',
	'rel' => [
		'main.core',
		'mobile.imageviewer',
		'ui.icons.disk',
		'mobile.ajax',
		'ui.vue.components.audioplayer',
		'ui.vue'
	],
	'skip_core' => false,
];