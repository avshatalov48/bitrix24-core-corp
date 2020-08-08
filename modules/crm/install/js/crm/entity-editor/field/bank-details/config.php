<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"js" => "/bitrix/js/crm/entity-editor/field/bank-details/dist/bank-details.bundle.js",
	'rel' =>  [
		'crm.entity-editor.field.fieldset'
	],
	'skip_core' => true,
);