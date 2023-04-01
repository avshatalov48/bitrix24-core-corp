<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/adding-popup.bundle.css',
	'js' => 'dist/adding-popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.buttons',
		'crm.activity.todo-editor',
		'main.core.events',
		'ui.notification',
	],
	'skip_core' => false,
];
