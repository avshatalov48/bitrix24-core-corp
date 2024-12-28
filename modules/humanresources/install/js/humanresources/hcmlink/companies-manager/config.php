<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/companies-manager.bundle.css',
	'js' => 'dist/companies-manager.bundle.js',
	'rel' => [
		'main.core',
		'ui.sidepanel.layout',
		'humanresources.hcmlink.company-connect-page',
	],
	'skip_core' => false,
];