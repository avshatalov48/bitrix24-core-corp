<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/copilot-image-controller.bundle.css',
	'js' => 'dist/copilot-image-controller.bundle.js',
	'rel' => [
		'ai.engine',
		'ai.ajax-error-handler',
		'main.loader',
		'ui.buttons',
		'main.core.events',
		'main.popup',
		'main.core',
		'ui.icon-set.api.core',
	],
	'skip_core' => false,
];