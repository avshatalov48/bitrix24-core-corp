<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/comment-action-controller.bundle.css',
	'js' => 'dist/comment-action-controller.bundle.js',
	'rel' => [
		'main.core',
		'rest.client',
	],
	'skip_core' => false,
];