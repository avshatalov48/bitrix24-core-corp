<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/item-details-component.css',
	'js' => 'dist/item-details-component.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.dialogs.messagebox',
		'ui.stageflow',
		'crm.stage-model',
		'main.loader',
		'main.popup',
		'crm.messagesender',
	],
	'skip_core' => false,
];
