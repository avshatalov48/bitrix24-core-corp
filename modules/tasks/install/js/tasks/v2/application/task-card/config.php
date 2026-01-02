<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/task-card.bundle.css',
	'js' => 'dist/task-card.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'ui.system.skeleton',
	],
	'skip_core' => false,
];
