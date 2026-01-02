<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/open-group.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'tasks.v2.lib.href-click',
		'tasks.v2.provider.service.group-service',
	],
	'skip_core' => true,
];
