<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/list-editor.bundle.css',
	'js' => 'dist/list-editor.bundle.js',
	'rel' => [
		'ui.notification',
		'ui.draganddrop.draggable',
		'ui.sidepanel.layout',
		'ui.buttons',
		'main.loader',
		'main.core.events',
		'ui.forms',
		'main.core',
		'crm.form.fields.selector',
	],
	'skip_core' => false,
];