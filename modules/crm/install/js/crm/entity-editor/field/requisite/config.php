<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"css" => "dist/requisite.bundle.css",
	"js" => "dist/requisite.bundle.js",
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.design-tokens',
		'main.popup',
		'main.loader',
		'crm.entity-editor',
		'crm.entity-editor.field.address',
		'crm.entity-editor.field.requisite.autocomplete',
		'ui.dialogs.messagebox'
	],
	'skip_core' => false,
);