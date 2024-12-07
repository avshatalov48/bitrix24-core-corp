<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'css' => 'dist/reinvite-popup.bundle.css',
	'js' => array(
		'dist/reinvite-popup.bundle.js'
	),
	'rel' => [
		'main.popup',
		'ui.buttons',
		'main.core',
	],
	'skip_core' => false,
);