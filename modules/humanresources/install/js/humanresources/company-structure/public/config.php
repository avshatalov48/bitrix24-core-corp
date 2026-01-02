<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\HumanResources\Config\Storage;

return [
	'css' => 'dist/public.bundle.css',
	'js' => 'dist/public.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
	'settings' => [
		'url' => CModule::IncludeModule('humanresources') ? Storage::getVisualStructurePublicUrl() : null,
	],
];
