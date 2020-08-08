<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"css" => "dist/address.bundle.css",
	"js" => "dist/address.bundle.js",
	'rel' => [
		'ui.entity-editor',
		'crm.entity-editor.field.address.base',
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
);