<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/copilot-chat.bundle.css',
	'js' => 'dist/copilot-chat.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ai.copilot-chat.ui',
	],
	'skip_core' => true,
];
