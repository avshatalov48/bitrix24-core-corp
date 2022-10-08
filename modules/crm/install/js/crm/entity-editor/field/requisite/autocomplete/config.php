<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"js" => "dist/autocomplete.bundle.js",
	"css" => "dist/autocomplete.bundle.css",
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.design-tokens',
		'ui.feedback.form',
		'ui.common',
		'ui.dropdown',
		'ui.buttons',
		'ui.forms',
		'ui.dialogs.messagebox',
		'crm.placement.detailsearch',
	],
	'skip_core' => false,
);