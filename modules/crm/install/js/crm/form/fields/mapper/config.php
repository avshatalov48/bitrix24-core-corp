<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/mapper.bundle.css',
	'js' => 'dist/mapper.bundle.js',
	'rel' => [
		'ui.sidepanel-content',
		'main.core',
		'main.core.events',
		'landing.ui.collection.buttoncollection',
		'landing.ui.collection.formcollection',
		'landing.ui.panel.fieldspanel',
	],
	'skip_core' => false,
];