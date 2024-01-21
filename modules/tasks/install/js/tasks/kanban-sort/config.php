<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/kanban-sort.bundle.css',
	'js' => 'dist/kanban-sort.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
	],
	'skip_core' => false,
];