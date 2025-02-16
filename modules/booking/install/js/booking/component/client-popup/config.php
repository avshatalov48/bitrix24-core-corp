<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/client-popup.bundle.css',
	'js' => 'dist/client-popup.bundle.js',
	'rel' => [
		'booking.component.popup',
		'ui.notification-manager',
		'booking.provider.service.client-service',
		'booking.component.button',
		'main.core.events',
		'main.popup',
		'ui.icon-set.api.vue',
		'ui.icon-set.main',
		'ui.icon-set.actions',
		'ui.icon-set.crm',
		'ui.dropdown',
		'main.core',
		'ui.vue3',
		'booking.const',
		'phone_number',
		'crm.entity-editor.field.phone-number-input',
	],
	'skip_core' => false,
];
