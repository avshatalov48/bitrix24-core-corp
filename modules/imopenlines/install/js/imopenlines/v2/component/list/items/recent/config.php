<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/recent-list.bundle.css',
	'js' => 'dist/recent-list.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'im.v2.application.core',
		'imopenlines.v2.const',
		'imopenlines.v2.provider.service',
		'im.v2.component.elements',
		'im.v2.const',
		'im.v2.lib.date-formatter',
		'im.v2.lib.utils',
		'im.v2.lib.parser',
	],
	'skip_core' => true,
];
