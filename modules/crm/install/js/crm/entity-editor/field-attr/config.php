<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CJSCore::RegisterExt('crm_phase_rel', [
	'js' => [
		'/bitrix/js/crm/phase.js',
	],
]);

return [
	'js' => [
		'/bitrix/js/crm/entity-editor/field-attr/field-attr.js',
		'/bitrix/js/crm/entity-editor/field-attr/field-attr-configurator.js',
		'/bitrix/js/crm/entity-editor/field-attr/field-attr-phase-group.js',
	],
	'rel' => [
		'crm_phase_rel',
	],
];
