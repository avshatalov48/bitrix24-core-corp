<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;

$settings = [
	'region' => mb_strtolower(Application::getInstance()->getLicense()->getRegion() ?? ''),
];

return [
	'css' => 'dist/segment.bundle.css',
	'js' => 'dist/segment.bundle.js',
	'rel' => [
		'ui.vue3',
		'crm.integration.analytics',
		'ui.analytics',
		'ui.bbcode.parser',
		'ui.info-helper',
		'ui.notification',
		'ui.promo-video-popup',
		'ui.switcher',
		'ui.text-editor',
		'ui.buttons',
		'main.core',
		'main.core.events',
		'ui.entity-selector',
		'ui.design-tokens',
		'ui.design-tokens.air',
	],
	'skip_core' => false,
	'settings' => $settings,
];