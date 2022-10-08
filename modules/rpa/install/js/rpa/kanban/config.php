<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'/bitrix/js/rpa/kanban/src/kanban.css'
	],
	'js' => '/bitrix/js/rpa/kanban/dist/kanban.bundle.js',
	'rel' => [
		'ui.design-tokens',
		'ui.buttons',
		'ui.notification',
		'ui.fonts.opensans',
		'main.kanban',
		'rpa.kanban',
		'main.core',
		'rpa.manager',
		'ui.dialogs.messagebox',
		'main.popup',
		'rpa.fieldspopup',
	],
	'skip_core' => false,
];