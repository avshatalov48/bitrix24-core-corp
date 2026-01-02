<?php

use Bitrix\Booking\Internals\Container;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/settings.bundle.js',
	'css' => 'dist/settings.bundle.css',
	'rel' => [
		'main.loader',
		'booking.provider.service.crm-form-service',
		'main.core.events',
		'booking.const',
		'ui.entity-selector',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'isToolDisabled' => (
			Loader::includeModule('booking')
			&& Container::getIntranetBookingTool()->isDisabled()
		),
	],
];
