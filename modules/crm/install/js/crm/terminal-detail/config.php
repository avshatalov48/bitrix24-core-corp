<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/terminal.bundle.css',
	'js' => 'dist/terminal.bundle.js',
	'rel' => [
		'ui.feedback.form',
		'main.core',
	],
	'skip_core' => false,
];
