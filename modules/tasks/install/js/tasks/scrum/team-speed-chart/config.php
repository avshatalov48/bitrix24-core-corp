<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/team.speed.chart.bundle.css',
	'js' => 'dist/team.speed.chart.bundle.js',
	'rel' => [
		'main.core',
		'amcharts4',
		'amcharts4_theme_animated',
		'ui.sidepanel.layout',
	],
	'skip_core' => false,
];