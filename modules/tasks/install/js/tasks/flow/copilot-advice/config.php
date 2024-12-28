<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/copilot-advice.bundle.css',
	'js' => 'dist/copilot-advice.bundle.js',
	'rel' => [
		'tasks.flow.edit-form',
		'ui.icon-set.api.core',
		'ui.icon-set.main',
		'ai.copilot-chat.ui',
		'main.core',
	],
	'skip_core' => false,
];
