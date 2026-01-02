<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/field.bundle.css',
	'js' => 'dist/field.bundle.js',
	'rel' => [
		'main.core',
		'booking.const',
		'booking.lib.segments',
		'booking.lib.slot-ranges',
		'ui.date-picker',
		'main.date',
		'main.loader',
		'booking.component.mixin.loc-mixin',
	],
	'skip_core' => false,
];
