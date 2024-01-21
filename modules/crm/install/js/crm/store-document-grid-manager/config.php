<?php

use Bitrix\Catalog\Config\State;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$isProductBatchMethodSelected = false;
if (Loader::includeModule('catalog'))
{
	$isProductBatchMethodSelected = State::isProductBatchMethodSelected();
}


return [
	'css' => 'dist/store-document-grid-manager.bundle.css',
	'js' => 'dist/store-document-grid-manager.bundle.js',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'main.core.events',
		'main.core',
		'ui.dialogs.messagebox',
	],
	'skip_core' => false,
	'settings' => [
		'isProductBatchMethodSelected' => $isProductBatchMethodSelected,
	],
];