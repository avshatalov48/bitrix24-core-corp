<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/task.status.bundle.css',
	'js' => 'dist/task.status.bundle.js',
	'rel' => [
		'ui.notification',
		'tasks.scrum.dod',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
];