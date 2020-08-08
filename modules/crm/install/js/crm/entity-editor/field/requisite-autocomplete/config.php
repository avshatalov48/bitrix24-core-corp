<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"js" => "dist/requisite-autocomplete.bundle.js",
	'rel' => [
		'main.core.events',
		'main.core',
		'crm.entity-editor.field.requisite.autocomplete',
	],
	'skip_core' => false,
);