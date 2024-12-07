<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'main.core',
		'ui.stageflow',
		'ui.buttons',
		'crm.stage.permission-checker',
		'crm.stage-model',
	],
	'skip_core' => false,
];
