<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sprint.completion.form.bundle.css',
	'js' => 'dist/sprint.completion.form.bundle.js',
	'rel' => [
		'main.core.events',
		'ui.sidepanel.layout',
		'main.core',
		'ui.hint',
	],
	'skip_core' => false,
];