<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '/bitrix/js/rpa/timeline/src/timeline.css',
	'js' => '/bitrix/js/rpa/timeline/dist/timeline.bundle.js',
	'rel' => [
		'main.core.events',
		'rpa.manager',
		'main.popup',
		'main.core',
		'ui.timeline',
	],
	'skip_core' => false,
];