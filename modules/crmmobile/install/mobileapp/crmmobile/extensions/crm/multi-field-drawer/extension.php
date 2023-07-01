<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$multiFields = [];

foreach (CCrmFieldMulti::GetEntityTypes() as $fmType => $fields)
{
	foreach ($fields as $type => $field)
	{
		$multiFields[$fmType][$type] = $field['SHORT'] ?? $field['FULL'] ?? '';
	}
}

return [
	'multiFields' => $multiFields,
];
