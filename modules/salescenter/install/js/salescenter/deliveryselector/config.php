<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'dist/deliveryselector.bundle.css',
	],
	'js' => 'dist/deliveryselector.bundle.js',
	'rel' => [
		'main.core',
		'salescenter.manager',
		'ui.ears',
		'ui.vue',
		'location.core',
		'location.widget',
		'ui.notification',
		'main.popup',
		'salescenter.component.stage-block.hint',
		'currency',
		'ui.design-tokens',
		'ui.fonts.opensans',
	],
	'skip_core' => false,
];