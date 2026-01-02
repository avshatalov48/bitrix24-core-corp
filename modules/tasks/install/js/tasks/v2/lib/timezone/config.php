<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER;
if (!is_object($USER))
{
	return [];
}

return [
	'js' => 'dist/timezone.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'timeZone' => $USER->GetParam('TIME_ZONE'),
	],
];
