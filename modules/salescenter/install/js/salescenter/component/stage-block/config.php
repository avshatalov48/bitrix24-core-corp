<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/stage-block.bundle.css',
	'js' => 'dist/stage-block.bundle.js',
	'rel' => [
		'main.polyfill.core',
	],
	'skip_core' => true,
];