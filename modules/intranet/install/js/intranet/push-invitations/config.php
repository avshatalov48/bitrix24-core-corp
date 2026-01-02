<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/style.css',
	'js' => 'dist/push-invitations.bundle.js',
	'rel' => [
		'ui.notification',
		'main.core',
	],
	'skip_core' => false,
];
