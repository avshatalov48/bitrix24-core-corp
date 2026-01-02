<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/air-sidepanel.bundle.js',
	'rel' => [
		'sidepanel',
		'intranet.sidepanel.bindings',
		'intranet.sidepanel.external',
	],
];
