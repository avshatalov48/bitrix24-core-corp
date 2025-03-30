<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/disk.boards-promo-popup.bundle.js',
	'rel' => [
		'main.core',
		'ui.buttons',
		'ui.promo-video-popup',
	],
	'skip_core' => false,
];
