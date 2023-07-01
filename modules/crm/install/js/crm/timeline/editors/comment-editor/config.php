<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/comment-editor.bundle.css',
	'js' => 'dist/comment-editor.bundle.js',
	'rel' => [
		'main.core',
		'main.loader',
		'ui.notification',
	],
	'skip_core' => false,
];
