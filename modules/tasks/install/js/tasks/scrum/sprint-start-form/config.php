<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sprint.start.form.bundle.css',
	'js' => 'dist/sprint.start.form.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];