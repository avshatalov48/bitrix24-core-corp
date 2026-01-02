<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/copilot-chat.bundle.js',
	'rel' => [
		'ai.copilot-chat.ui',
		'main.core.events',
		'main.core',
		'pull.client',
	],
	'skip_core' => false,
];
