<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-mini-profile.bundle.css',
	'js' => 'dist/user-mini-profile.bundle.js',
	'rel' => [
		'main.core.cache',
		'ui.vue3.components.button',
		'ui.vue3',
		'ui.icon-set.outline',
		'humanresources.company-structure.public',
		'ui.vue3.components.menu',
		'ui.icon-set.api.core',
		'main.date',
		'ui.notification',
		'ui.vue3.components.rich-menu',
		'ui.icon-set.api.vue',
		'ui.vue3.components.avatar',
		'main.core.events',
		'main.core',
		'main.popup',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
