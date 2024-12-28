<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/grid.bundle.css',
	'js' => [
		'dist/grid.bundle.js'
	],
	'skip_core' => false,
	'rel' => [
		'ui.avatar',
		'ui.label',
		'ui.form-elements.field',
		'main.popup',
		'ui.cnt',
		'intranet.reinvite',
		'ui.icon-set.main',
		'ui.dialogs.messagebox',
		'im.public',
		'ui.entity-selector',
		'main.core',
	],
];