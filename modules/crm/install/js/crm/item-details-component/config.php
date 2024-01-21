<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/item-details-component.css',
	'js' => 'dist/item-details-component.bundle.js',
	'rel' => [
		'crm.messagesender',
		'crm.stage-model',
		'main.core',
		'main.core.events',
		'main.loader',
		'main.popup',
		'ui.dialogs.messagebox',
		'ui.stageflow',
	],
	'skip_core' => false,
];
