<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'biconnector.entity-editor.field.settings-date-filter',
	],
	'skip_core' => false,
];