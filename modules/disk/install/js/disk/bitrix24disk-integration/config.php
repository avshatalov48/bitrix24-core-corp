<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'./dist/disk.bitrix24disk-integration.bundle.js',
	],
	'rel' => [
		'main.core',
		'im.v2.lib.utils',
		'im.v2.lib.desktop-api',
		'im.v2.const',
	],
	'skip_core' => false,
];
