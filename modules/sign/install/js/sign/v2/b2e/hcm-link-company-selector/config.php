<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hcm-link-company-selector.bundle.css',
	'js' => 'dist/hcm-link-company-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
		'sign.v2.api',
		'main.loader',
		'humanresources.hcmlink.company-connect-page',
	],
	'skip_core' => false,
];