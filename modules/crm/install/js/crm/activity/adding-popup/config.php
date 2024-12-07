<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/adding-popup.bundle.css',
	'js' => 'dist/adding-popup.bundle.js',
	'rel' => [
		'crm.activity.todo-editor-v2',
		'main.core',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'ui.notification',
	],
	'skip_core' => false,
];
