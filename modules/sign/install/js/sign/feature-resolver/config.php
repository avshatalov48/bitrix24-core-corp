<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Sign\FeatureResolver;


$codes = Loader::includeModule('sign') ? FeatureResolver::instance()->getCodes() : [];

return [
	'css' => 'dist/feature-resolver.bundle.css',
	'js' => 'dist/feature-resolver.bundle.js',
	'rel' => [
		'main.core',
	],
	'settings' => [
		'featureCodes' => $codes,
	],
	'skip_core' => false,
];
