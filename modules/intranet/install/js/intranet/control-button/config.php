<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'css' => '/bitrix/js/intranet/control-button/style.css',
	'js' => array(
		'dist/control-button.bundle.js'
	),
	'rel' => [
		'main.core',
		'main.popup',
		'pull.client',
		'main.core.events',
	],
	'skip_core' => false,
);
