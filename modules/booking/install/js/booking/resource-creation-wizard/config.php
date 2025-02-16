<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/resource-creation-wizard.bundle.css',
	'js' => 'dist/resource-creation-wizard.bundle.js',
	'rel' => [
		'ui.vue3',
		'booking.component.mixin.loc-mixin',
		'booking.model.notifications',
		'booking.model.resource-creation-wizard',
		'booking.lib.side-panel-instance',
		'main.loader',
		'booking.core',
		'ui.notification-manager',
		'booking.provider.service.resources-service',
		'crm.messagesender',
		'ui.icon-set.actions',
		'booking.provider.service.resource-creation-wizard-service',
		'ui.entity-selector',
		'booking.model.resource-types',
		'booking.provider.service.resources-type-service',
		'main.core.events',
		'ui.buttons',
		'booking.lib.duration',
		'ui.forms',
		'ui.layout-form',
		'ui.design-tokens',
		'booking.lib.aha-moments',
		'main.date',
		'booking.component.popup',
		'main.popup',
		'main.core',
		'ui.vue3.directives.hint',
		'ui.icon-set.api.vue',
		'ui.icon-set.crm',
		'ui.hint',
		'booking.component.switcher',
		'ui.label',
		'ui.vue3.vuex',
		'ui.icon-set.main',
		'booking.component.button',
		'booking.const',
		'booking.lib.help-desk',
	],
	'skip_core' => false,
];
