<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/item-details-component.bundle.css',
	'js' => 'dist/item-details-component.bundle.js',
	'rel' => [
		'crm.item-details-component.pagetitle',
		'crm.item-details-component.stage-flow',
		'crm.messagesender',
		'crm.stage-model',
		'crm.stage.permission-checker',
		'main.core',
		'main.core.events',
		'main.loader',
		'ui.dialogs.messagebox',
		'ui.stageflow',
		'crm.integration.analytics',
	],
	'skip_core' => false,
];
