<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/burn.down.chart.bundle.css',
	'js' => 'dist/burn.down.chart.bundle.js',
	'rel' => [
		'main.core',
		'amcharts4',
		'amcharts4_theme_animated',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];