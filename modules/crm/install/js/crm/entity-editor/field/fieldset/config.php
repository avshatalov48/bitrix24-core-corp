<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"css" => "/bitrix/js/crm/entity-editor/field/fieldset/dist/fieldset.bundle.css",
	"js" => "/bitrix/js/crm/entity-editor/field/fieldset/dist/fieldset.bundle.js",
	'rel' =>  [
		'ui.entity-editor',
		'fx',
		'ui.design-tokens',
	],
	'skip_core' => true,
);