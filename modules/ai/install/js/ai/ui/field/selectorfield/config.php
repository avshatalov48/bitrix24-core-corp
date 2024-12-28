<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/selectorfield.bundle.css',
	'js' => 'dist/selectorfield.bundle.js',
	'rel' => [
		'ui.form-elements.view',
		'main.core',
		'types.js',
	],
	'skip_core' => false,
];