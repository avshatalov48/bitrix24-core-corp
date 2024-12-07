<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/textpayload.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ai.payload.basepayload',
	],
	'skip_core' => true,
];