<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

CJSCore::RegisterExt('crm_phase_rel', array(
	'js' => [
		'/bitrix/js/crm/phase.js',
	],
));

return array(
	"js" => [
		"/bitrix/js/crm/entity-editor/field-attr/field-attr.js",
	],
	"rel" => [
		"crm_phase_rel",
	]
);
