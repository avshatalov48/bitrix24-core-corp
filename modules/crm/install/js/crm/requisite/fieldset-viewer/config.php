<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/fieldset-viewer.bundle.css',
	'js' => 'dist/fieldset-viewer.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.popup',
		'main.loader',
		'ui.buttons',
		'crm.field.list-editor',
	],
	'skip_core' => false,
];